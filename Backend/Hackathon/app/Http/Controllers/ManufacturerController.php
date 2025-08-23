<?php

namespace App\Http\Controllers;

use App\Models\ManufacturerStock;
use App\Models\DistributorStock;
use App\Models\VanStock;
use App\Models\Requisition;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManufacturerController extends Controller
{
    /**
     * Get comprehensive dashboard data
     */
    public function getDashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isManufacturer()) {
            return response()->json([
                'message' => 'Unauthorized. Only manufacturers can access this resource.'
            ], 403);
        }

        // Get manufacturer warehouse stock
        $manufacturerStock = $this->getManufacturerStock();
        
        // Get distributor stock summary
        $distributorStock = $this->getDistributorStockSummary();
        
        // Get van stock summary
        $vanStock = $this->getVanStockSummary();
        
        // Get pending requisitions
        $pendingRequisitions = $this->getPendingRequisitionsSummary();
        
        // Get overall statistics
        $overallStats = $this->getOverallStatistics();

        return response()->json([
            'dashboard' => [
                'manufacturer_stock' => $manufacturerStock,
                'distributor_stock' => $distributorStock,
                'van_stock' => $vanStock,
                'pending_requisitions' => $pendingRequisitions,
                'statistics' => $overallStats
            ]
        ]);
    }

    /**
     * Get manufacturer warehouse stock
     */
    public function getManufacturerStock()
    {
        $manufacturerStock = ManufacturerStock::with('product')
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name,
                    'unit' => $stock->product->unit,
                    'quantity' => $stock->quantity,
                    'updated_at' => $stock->updated_at
                ];
            });

        return [
            'stock' => $manufacturerStock,
            'summary' => [
                'total_products' => $manufacturerStock->count(),
                'total_items' => $manufacturerStock->sum('quantity')
            ]
        ];
    }

    /**
     * Get distributor stock summary
     */
    public function getDistributorStockSummary()
    {
        // Get stock by distributor
        $distributorStockByUser = DistributorStock::with(['product', 'user'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($userStock, $userId) {
                $user = $userStock->first()->user;
                return [
                    'distributor_id' => $userId,
                    'distributor_name' => $user->name,
                    'distributor_email' => $user->email,
                    'products' => $userStock->map(function ($stock) {
                        return [
                            'product_id' => $stock->product_id,
                            'product_name' => $stock->product->name,
                            'unit' => $stock->product->unit,
                            'quantity' => $stock->quantity
                        ];
                    })->values(),
                    'total_items' => $userStock->sum('quantity')
                ];
            })->values();

        // Get product totals across all distributors
        $productTotals = DistributorStock::with('product')
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->groupBy('product_id')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'unit' => $item->product->unit,
                    'total_quantity' => $item->total_quantity
                ];
            });

        return [
            'by_distributor' => $distributorStockByUser,
            'product_totals' => $productTotals,
            'summary' => [
                'total_distributors' => $distributorStockByUser->count(),
                'total_items' => $productTotals->sum('total_quantity')
            ]
        ];
    }

    /**
     * Get van stock summary
     */
    public function getVanStockSummary()
    {
        // Get stock by van rep
        $vanStockByUser = VanStock::with(['product', 'user'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($userStock, $userId) {
                $user = $userStock->first()->user;
                return [
                    'van_rep_id' => $userId,
                    'van_rep_name' => $user->name,
                    'van_rep_email' => $user->email,
                    'van_capacity' => $user->van_capacity,
                    'current_usage' => $userStock->sum('quantity'),
                    'utilization_percentage' => $user->van_capacity ? 
                        round(($userStock->sum('quantity') / $user->van_capacity) * 100, 2) : 0,
                    'products' => $userStock->map(function ($stock) {
                        return [
                            'product_id' => $stock->product_id,
                            'product_name' => $stock->product->name,
                            'unit' => $stock->product->unit,
                            'quantity' => $stock->quantity
                        ];
                    })->values()
                ];
            })->values();

        // Get product totals across all vans
        $productTotals = VanStock::with('product')
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->groupBy('product_id')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'unit' => $item->product->unit,
                    'total_quantity' => $item->total_quantity
                ];
            });

        return [
            'by_van_rep' => $vanStockByUser,
            'product_totals' => $productTotals,
            'summary' => [
                'total_van_reps' => $vanStockByUser->count(),
                'total_items' => $productTotals->sum('total_quantity'),
                'average_utilization' => $vanStockByUser->avg('utilization_percentage')
            ]
        ];
    }

    /**
     * Get pending requisitions summary
     */
    public function getPendingRequisitionsSummary()
    {
        $pendingRequisitions = Requisition::with(['vanRep', 'distributor', 'product'])
            ->where('status', Requisition::STATUS_PENDING)
            ->orderBy('date_requested', 'asc')
            ->get()
            ->map(function ($requisition) {
                return [
                    'id' => $requisition->id,
                    'van_rep' => [
                        'id' => $requisition->vanRep->id,
                        'name' => $requisition->vanRep->name
                    ],
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
                    'date_requested' => $requisition->date_requested,
                    'created_at' => $requisition->created_at
                ];
            });

        // Group by product for summary
        $productSummary = $pendingRequisitions->groupBy('product.id')
            ->map(function ($requisitions, $productId) {
                $firstReq = $requisitions->first();
                return [
                    'product_id' => $productId,
                    'product_name' => $firstReq['product']['name'],
                    'unit' => $firstReq['product']['unit'],
                    'total_requested' => $requisitions->sum('quantity'),
                    'request_count' => $requisitions->count()
                ];
            })->values();

        return [
            'requisitions' => $pendingRequisitions,
            'product_summary' => $productSummary,
            'summary' => [
                'total_pending' => $pendingRequisitions->count(),
                'total_quantity_requested' => $pendingRequisitions->sum('quantity')
            ]
        ];
    }

    /**
     * Get overall statistics
     */
    public function getOverallStatistics()
    {
        // Get stock distribution
        $manufacturerTotal = ManufacturerStock::sum('quantity');
        $distributorTotal = DistributorStock::sum('quantity');
        $vanTotal = VanStock::sum('quantity');
        $grandTotal = $manufacturerTotal + $distributorTotal + $vanTotal;

        // Get user counts
        $userCounts = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // Get requisition statistics
        $requisitionStats = Requisition::selectRaw('
            status,
            COUNT(*) as count,
            SUM(quantity) as total_quantity
        ')
        ->groupBy('status')
        ->get()
        ->keyBy('status');

        // Get recent activity (last 7 days)
        $recentActivity = StockMovement::where('date', '>=', Carbon::today()->subDays(6))
            ->count();

        return [
            'stock_distribution' => [
                'manufacturer' => [
                    'quantity' => $manufacturerTotal,
                    'percentage' => $grandTotal ? round(($manufacturerTotal / $grandTotal) * 100, 2) : 0
                ],
                'distributor' => [
                    'quantity' => $distributorTotal,
                    'percentage' => $grandTotal ? round(($distributorTotal / $grandTotal) * 100, 2) : 0
                ],
                'van' => [
                    'quantity' => $vanTotal,
                    'percentage' => $grandTotal ? round(($vanTotal / $grandTotal) * 100, 2) : 0
                ],
                'total' => $grandTotal
            ],
            'user_counts' => [
                'manufacturers' => $userCounts['manufacturer'] ?? 0,
                'distributors' => $userCounts['distributor'] ?? 0,
                'van_reps' => $userCounts['van_rep'] ?? 0
            ],
            'requisition_stats' => [
                'pending' => [
                    'count' => $requisitionStats->get(Requisition::STATUS_PENDING)->count ?? 0,
                    'quantity' => $requisitionStats->get(Requisition::STATUS_PENDING)->total_quantity ?? 0
                ],
                'approved' => [
                    'count' => $requisitionStats->get(Requisition::STATUS_APPROVED)->count ?? 0,
                    'quantity' => $requisitionStats->get(Requisition::STATUS_APPROVED)->total_quantity ?? 0
                ],
                'rejected' => [
                    'count' => $requisitionStats->get(Requisition::STATUS_REJECTED)->count ?? 0,
                    'quantity' => $requisitionStats->get(Requisition::STATUS_REJECTED)->total_quantity ?? 0
                ]
            ],
            'recent_activity_count' => $recentActivity
        ];
    }

    /**
     * Get stock movement history
     */
    public function getStockMovementHistory(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isManufacturer()) {
            return response()->json([
                'message' => 'Unauthorized. Only manufacturers can access this resource.'
            ], 403);
        }

        // Get query parameters
        $dateFrom = $request->query('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->query('date_to', Carbon::today()->format('Y-m-d'));
        $productId = $request->query('product_id');
        $limit = $request->query('limit', 50);

        $query = StockMovement::with(['product', 'fromUser', 'toUser'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $movements = $query->limit($limit)->get()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'product' => [
                    'id' => $movement->product->id,
                    'name' => $movement->product->name,
                    'unit' => $movement->product->unit
                ],
                'from' => [
                    'role' => $movement->from_role,
                    'user' => $movement->fromUser ? [
                        'id' => $movement->fromUser->id,
                        'name' => $movement->fromUser->name
                    ] : null
                ],
                'to' => [
                    'role' => $movement->to_role,
                    'user' => $movement->toUser ? [
                        'id' => $movement->toUser->id,
                        'name' => $movement->toUser->name
                    ] : null
                ],
                'quantity' => $movement->quantity,
                'date' => $movement->date,
                'created_at' => $movement->created_at
            ];
        });

        // Get summary statistics for the period
        $summary = StockMovement::whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw('
                COUNT(*) as total_movements,
                SUM(quantity) as total_quantity_moved,
                COUNT(DISTINCT product_id) as products_involved
            ')
            ->first();

        return response()->json([
            'stock_movements' => $movements,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'summary' => [
                'total_movements' => $summary->total_movements ?? 0,
                'total_quantity_moved' => $summary->total_quantity_moved ?? 0,
                'products_involved' => $summary->products_involved ?? 0
            ]
        ]);
    }

    /**
     * Get products list
     */
    public function getProducts(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isManufacturer()) {
            return response()->json([
                'message' => 'Unauthorized. Only manufacturers can access this resource.'
            ], 403);
        }

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
}