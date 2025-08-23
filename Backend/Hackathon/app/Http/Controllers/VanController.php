<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\VanStock;
use App\Models\Requisition;
use App\Models\DistributorStock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VanController extends Controller
{
    /**
     * Get van stock for the authenticated van rep
     */
    public function getVanStock(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isRep()) {
            return response()->json([
                'message' => 'Unauthorized. Only representatives can access this resource.'
            ], 403);
        }

        $vanStock = VanStock::with('product')
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

        $totalCapacityUsed = $vanStock->sum('quantity');

        return response()->json([
            'van_stock' => $vanStock,
            'capacity_info' => [
                'total_capacity' => $user->van_capacity,
                'used_capacity' => $totalCapacityUsed,
                'available_capacity' => $user->van_capacity - $totalCapacityUsed
            ]
        ]);
    }

    /**
     * Get all products available for requisition
     */
    public function getProducts()
    {
        $products = Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit
            ];
        });

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Create a new requisition request
     */
    public function createRequisition(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isRep()) {
            return response()->json([
                'message' => 'Unauthorized. Only representatives can create requisitions.'
            ], 403);
        }

        $validated = $request->validate([
            'distributor_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'date_requested' => 'nullable|date'
        ]);

        // Set default date to today if not provided
        $validated['date_requested'] = $validated['date_requested'] ?? Carbon::today()->format('Y-m-d');

        // Verify distributor role
        $distributor = User::find($validated['distributor_id']);
        if (!$distributor->isDistributor()) {
            return response()->json([
                'message' => 'Invalid distributor. User must have distributor role.'
            ], 400);
        }

        // Check for duplicate request (same product, same day)
        $existingRequisition = Requisition::where('van_rep_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->where('date_requested', $validated['date_requested'])
            ->first();

        if ($existingRequisition) {
            return response()->json([
                'message' => 'Duplicate request. You have already requested this product for the selected date.',
                'existing_requisition' => [
                    'id' => $existingRequisition->id,
                    'quantity' => $existingRequisition->quantity,
                    'status' => $existingRequisition->status,
                    'date_requested' => $existingRequisition->date_requested
                ]
            ], 400);
        }

        // Check van capacity constraint
        if (!$user->canAddToVan($validated['quantity'])) {
            $currentCapacity = $user->getCurrentVanCapacity();
            $availableCapacity = $user->van_capacity - $currentCapacity;
            
            return response()->json([
                'message' => 'Van capacity exceeded. Cannot add requested quantity.',
                'capacity_info' => [
                    'total_capacity' => $user->van_capacity,
                    'current_usage' => $currentCapacity,
                    'available_capacity' => $availableCapacity,
                    'requested_quantity' => $validated['quantity']
                ]
            ], 400);
        }

        // Check if distributor has enough stock
        $distributorStock = DistributorStock::where('user_id', $validated['distributor_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if (!$distributorStock || $distributorStock->quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient distributor stock.',
                'available_stock' => $distributorStock ? $distributorStock->quantity : 0,
                'requested_quantity' => $validated['quantity']
            ], 400);
        }

        // Create the requisition
        $requisition = Requisition::create([
            'van_rep_id' => $user->id,
            'distributor_id' => $validated['distributor_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'status' => Requisition::STATUS_PENDING,
            'date_requested' => $validated['date_requested']
        ]);

        // Load relationships for response
        $requisition->load(['vanRep', 'distributor', 'product']);

        return response()->json([
            'message' => 'Requisition created successfully.',
            'requisition' => [
                'id' => $requisition->id,
                'van_rep' => $requisition->vanRep->name,
                'distributor' => $requisition->distributor->name,
                'product' => $requisition->product->name,
                'quantity' => $requisition->quantity,
                'status' => $requisition->status,
                'date_requested' => $requisition->date_requested,
                'created_at' => $requisition->created_at
            ]
        ], 201);
    }

    /**
     * Get requisitions for the authenticated van rep
     */
    public function getMyRequisitions(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isVanRep()) {
            return response()->json([
                'message' => 'Unauthorized. Only van representatives can access this resource.'
            ], 403);
        }

        // Get query parameters for filtering
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Requisition::with(['distributor', 'product'])
            ->where('van_rep_id', $user->id)
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

        $requisitions = $query->get()->map(function ($requisition) {
            return [
                'id' => $requisition->id,
                'distributor' => [
                    'id' => $requisition->distributor->id,
                    'name' => $requisition->distributor->name
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
                'updated_at' => $requisition->updated_at
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
     * Get available distributors for requisitions
     */
    public function getDistributors()
    {
        $distributors = User::distributors()
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'distributors' => $distributors
        ]);
    }

    /**
     * Get van capacity information
     */
    public function getCapacityInfo(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isVanRep()) {
            return response()->json([
                'message' => 'Unauthorized. Only van representatives can access this resource.'
            ], 403);
        }

        $currentCapacity = $user->getCurrentVanCapacity();
        
        return response()->json([
            'capacity_info' => [
                'total_capacity' => $user->van_capacity,
                'used_capacity' => $currentCapacity,
                'available_capacity' => $user->van_capacity - $currentCapacity,
                'utilization_percentage' => round(($currentCapacity / $user->van_capacity) * 100, 2)
            ]
        ]);
    }
}
