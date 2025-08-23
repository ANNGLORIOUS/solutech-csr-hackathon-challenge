<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\DistributorStock;
use App\Models\VanStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributorController extends Controller
{
    /**
     * Get all requisition requests for the authenticated distributor
     */
    public function getRequisitions(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isDistributor()) {
            return response()->json([
                'message' => 'Unauthorized. Only distributors can access this resource.'
            ], 403);
        }

        // Get query parameters for filtering
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Requisition::with(['vanRep', 'product'])
            ->where('distributor_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($status && in_array($status, [Requisition::STATUS_PENDING, Requisition::STATUS_APPROVED, Requisition::STATUS_REJECTED])) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('date_requested', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date_requested', '<=', $dateTo);
        }

        $requisitions = $query->get()->map(function ($requisition) use ($user) {
            // Check current distributor stock for this product
            $currentStock = DistributorStock::where('user_id', $user->id)
                ->where('product_id', $requisition->product_id)
                ->first();

            return [
                'id' => $requisition->id,
                'van_rep' => [
                    'id' => $requisition->vanRep->id,
                    'name' => $requisition->vanRep->name,
                    'email' => $requisition->vanRep->email
                ],
                'product' => [
                    'id' => $requisition->product->id,
                    'name' => $requisition->product->name,
                    'unit' => $requisition->product->unit
                ],
                'quantity' => $requisition->quantity,
                'status' => $requisition->status,
                'date_requested' => $requisition->date_requested,
                'created_at' => $requisition->created_at,
                'updated_at' => $requisition->updated_at,
                'can_approve' => $requisition->status === Requisition::STATUS_PENDING && 
                               $currentStock && 
                               $currentStock->quantity >= $requisition->quantity,
                'available_stock' => $currentStock ? $currentStock->quantity : 0
            ];
        });

        // Get summary statistics
        $summary = [
            'total_requisitions' => $requisitions->count(),
            'pending' => $requisitions->where('status', Requisition::STATUS_PENDING)->count(),
            'approved' => $requisitions->where('status', Requisition::STATUS_APPROVED)->count(),
            'rejected' => $requisitions->where('status', Requisition::STATUS_REJECTED)->count(),
        ];

        return response()->json([
            'requisitions' => $requisitions,
            'summary' => $summary
        ]);
    }

    /**
     * Approve or reject a requisition request
     */
    public function updateRequisitionStatus(Request $request, $requisitionId)
    {
        $user = $request->user();
        
        if (!$user->isDistributor()) {
            return response()->json([
                'message' => 'Unauthorized. Only distributors can update requisitions.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string|max:500'
        ]);

        $requisition = Requisition::with(['vanRep', 'product'])
            ->where('id', $requisitionId)
            ->where('distributor_id', $user->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'message' => 'Requisition not found or you do not have permission to update it.'
            ], 404);
        }

        if ($requisition->status !== Requisition::STATUS_PENDING) {
            return response()->json([
                'message' => 'Can only update pending requisitions.',
                'current_status' => $requisition->status
            ], 400);
        }

        // If approving, perform stock validation and transfer
        if ($validated['status'] === Requisition::STATUS_APPROVED) {
            return $this->approveRequisition($requisition, $validated['remarks'] ?? null);
        } else {
            return $this->rejectRequisition($requisition, $validated['remarks'] ?? null);
        }
    }

    /**
     * Approve a requisition and handle stock transfers
     */
    private function approveRequisition($requisition, $remarks = null)
    {
        return DB::transaction(function () use ($requisition, $remarks) {
            // Check distributor stock availability
            $distributorStock = DistributorStock::where('user_id', $requisition->distributor_id)
                ->where('product_id', $requisition->product_id)
                ->lockForUpdate()
                ->first();

            if (!$distributorStock) {
                return response()->json([
                    'message' => 'Product not found in distributor stock.',
                    'product' => $requisition->product->name
                ], 400);
            }

            if ($distributorStock->quantity < $requisition->quantity) {
                return response()->json([
                    'message' => 'Insufficient stock to approve this requisition.',
                    'available_stock' => $distributorStock->quantity,
                    'requested_quantity' => $requisition->quantity
                ], 400);
            }

            // Double-check van capacity
            $vanRep = $requisition->vanRep;
            if (!$vanRep->canAddToVan($requisition->quantity)) {
                return response()->json([
                    'message' => 'Van rep cannot accommodate this quantity due to capacity constraints.',
                    'van_capacity' => $vanRep->van_capacity,
                    'current_usage' => $vanRep->getCurrentVanCapacity(),
                    'requested_quantity' => $requisition->quantity
                ], 400);
            }

            // Decrease distributor stock
            $distributorStock->quantity -= $requisition->quantity;
            $distributorStock->save();

            // Increase or create van stock
            $vanStock = VanStock::where('user_id', $requisition->van_rep_id)
                ->where('product_id', $requisition->product_id)
                ->first();

            if ($vanStock) {
                $vanStock->quantity += $requisition->quantity;
                $vanStock->save();
            } else {
                VanStock::create([
                    'user_id' => $requisition->van_rep_id,
                    'product_id' => $requisition->product_id,
                    'quantity' => $requisition->quantity
                ]);
            }

            // Update requisition status
            $requisition->status = Requisition::STATUS_APPROVED;
            $requisition->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $requisition->product_id,
                'from_role' => StockMovement::ROLE_DISTRIBUTOR,
                'from_user_id' => $requisition->distributor_id,
                'to_role' => StockMovement::ROLE_VAN_REP,
                'to_user_id' => $requisition->van_rep_id,
                'quantity' => $requisition->quantity,
                'date' => Carbon::today()
            ]);

            return response()->json([
                'message' => 'Requisition approved successfully. Stock has been transferred.',
                'requisition' => [
                    'id' => $requisition->id,
                    'status' => $requisition->status,
                    'van_rep' => $requisition->vanRep->name,
                    'product' => $requisition->product->name,
                    'quantity' => $requisition->quantity,
                    'remarks' => $remarks
                ],
                'stock_update' => [
                    'distributor_remaining' => $distributorStock->quantity,
                    'van_new_total' => $vanStock ? $vanStock->quantity : $requisition->quantity
                ]
            ]);
        });
    }

    /**
     * Reject a requisition
     */
    private function rejectRequisition($requisition, $remarks = null)
    {
        $requisition->status = Requisition::STATUS_REJECTED;
        $requisition->save();

        return response()->json([
            'message' => 'Requisition rejected.',
            'requisition' => [
                'id' => $requisition->id,
                'status' => $requisition->status,
                'van_rep' => $requisition->vanRep->name,
                'product' => $requisition->product->name,
                'quantity' => $requisition->quantity,
                'remarks' => $remarks
            ]
        ]);
    }

    /**
     * Get distributor's current stock
     */
    public function getStock(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isDistributor()) {
            return response()->json([
                'message' => 'Unauthorized. Only distributors can access this resource.'
            ], 403);
        }

        $distributorStock = DistributorStock::with('product')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name,
                    'unit' => $stock->product->unit,
                    'quantity' => $stock->quantity,
                    'updated_at' => $stock->updated_at
                ];
            });

        $totalItems = $distributorStock->sum('quantity');
        $totalProducts = $distributorStock->count();

        return response()->json([
            'distributor_stock' => $distributorStock,
            'summary' => [
                'total_products' => $totalProducts,
                'total_items' => $totalItems
            ]
        ]);
    }

    /**
     * Get pending requisitions count (for dashboard)
     */
    public function getPendingCount(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isDistributor()) {
            return response()->json([
                'message' => 'Unauthorized. Only distributors can access this resource.'
            ], 403);
        }

        $pendingCount = Requisition::where('distributor_id', $user->id)
            ->where('status', Requisition::STATUS_PENDING)
            ->count();

        return response()->json([
            'pending_requisitions' => $pendingCount
        ]);
    }

    /**
     * Get distributor dashboard summary
     */
    public function getDashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isDistributor()) {
            return response()->json([
                'message' => 'Unauthorized. Only distributors can access this resource.'
            ], 403);
        }

        // Get stock summary
        $stockSummary = DistributorStock::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total_products, SUM(quantity) as total_items')
            ->first();

        // Get requisition summary
        $requisitionSummary = Requisition::where('distributor_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_requisitions,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected
            ')
            ->first();

        // Get today's activity
        $todayActivity = Requisition::where('distributor_id', $user->id)
            ->whereDate('updated_at', Carbon::today())
            ->count();

        return response()->json([
            'dashboard' => [
                'stock_summary' => [
                    'total_products' => $stockSummary->total_products ?? 0,
                    'total_items' => $stockSummary->total_items ?? 0
                ],
                'requisition_summary' => [
                    'total_requisitions' => $requisitionSummary->total_requisitions ?? 0,
                    'pending' => $requisitionSummary->pending ?? 0,
                    'approved' => $requisitionSummary->approved ?? 0,
                    'rejected' => $requisitionSummary->rejected ?? 0
                ],
                'today_activity' => $todayActivity
            ]
        ]);
    }
}
