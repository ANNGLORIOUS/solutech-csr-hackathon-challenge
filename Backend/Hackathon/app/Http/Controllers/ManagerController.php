<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\VanStock;
use App\Models\DistributorStock;
use App\Models\Requisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerController extends Controller
{
    public function stockOverview()
    {
        $manufacturerStock = Product::all()->map(fn($p) => [
            'product_id' => $p->id,
            'product_name' => $p->name,
            'total_quantity' => $p->manufacturerStock?->quantity ?? 0
        ]);

        $distributorStock = DistributorStock::with('product')->get()->groupBy('product_id')
            ->map(fn($stocks, $productId) => [
                'product_id' => $productId,
                'product_name' => $stocks->first()->product->name,
                'total_quantity' => $stocks->sum('quantity')
            ])->values();

        $vanStock = VanStock::with('product')->get()->groupBy('product_id')
            ->map(fn($stocks, $productId) => [
                'product_id' => $productId,
                'product_name' => $stocks->first()->product->name,
                'total_quantity' => $stocks->sum('quantity')
            ])->values();

        return response()->json([
            'manufacturer_stock' => $manufacturerStock,
            'distributor_stock' => $distributorStock,
            'van_stock' => $vanStock
        ]);
    }

    public function pendingRequisitions()
    {
        $requisitions = Requisition::with(['vanRep', 'distributor', 'product'])
            ->where('status', Requisition::STATUS_PENDING)
            ->orderBy('date_requested', 'desc')
            ->get();

        return response()->json($requisitions);
    }

    public function stockMovements()
    {
        $movements = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('users as from_user', 'stock_movements.from_user_id', '=', 'from_user.id')
            ->join('users as to_user', 'stock_movements.to_user_id', '=', 'to_user.id')
            ->select(
                'stock_movements.id',
                'products.name as product',
                'stock_movements.quantity',
                'from_user.name as from_user',
                'to_user.name as to_user',
                'stock_movements.action',
                'stock_movements.date'
            )
            ->orderBy('stock_movements.date', 'desc')
            ->get();

        return response()->json($movements);
    }
}
