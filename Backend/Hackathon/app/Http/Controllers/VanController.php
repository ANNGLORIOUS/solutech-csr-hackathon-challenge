<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VanStock;
use App\Models\Product;
use App\Models\Requisition;

class VanController extends Controller
{
    // Get current van stock
    public function getVanStock()
    {
        $user = auth()->user();
        if (!$user || !$user->isRep()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $vanStock = VanStock::with('product')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'quantity' => $item->quantity,
                'unit' => $item->product->unit ?? ''
            ]);

        return response()->json(['van_stock' => $vanStock]);
    }

    // Get all products
    public function getProducts()
    {
        return response()->json(['products' => Product::all(['id', 'name', 'unit'])]);
    }

    // Create new requisition and return updated data
    public function createRequisition(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->isRep()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'distributor_id' => 'required|exists:users,id',
        ]);

        // Check van capacity
        $usedCapacity = $user->getCurrentVanCapacity();
        $available = max(($user->van_capacity ?? 0) - $usedCapacity, 0);

        if ($validated['quantity'] > $available) {
            return response()->json(['message' => 'Van capacity exceeded.'], 400);
        }

        $requisition = Requisition::create([
            'van_rep_id' => $user->id,
            'distributor_id' => $validated['distributor_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'status' => Requisition::STATUS_PENDING,
            'date_requested' => now()->toDateString(),
        ]);

        // Return updated data
        $vanStock = VanStock::with('product')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'quantity' => $item->quantity,
                'unit' => $item->product->unit ?? ''
            ]);

        $requisitions = Requisition::with('product')
            ->where('van_rep_id', $user->id)
            ->orderByDesc('date_requested')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'product' => [
                    'id' => $r->product_id,
                    'name' => $r->product->name ?? 'Unknown',
                    'unit' => $r->product->unit ?? '',
                ],
                'quantity' => $r->quantity,
                'status' => $r->status,
                'date_requested' => $r->date_requested->toDateString(),
            ]);

        $total = $user->van_capacity ?? 0;
        $used = $user->getCurrentVanCapacity();
        $available = max($total - $used, 0);

        return response()->json([
            'requisition' => $requisition->load('product'),
            'van_stock' => $vanStock,
            'requisitions' => $requisitions,
            'capacity_info' => [
                'total_capacity' => $total,
                'used_capacity' => $used,
                'available_capacity' => $available
            ]
        ], 201);
    }

    // Get my requisitions
    public function getMyRequisitions()
    {
        $user = auth()->user();
        if (!$user || !$user->isRep()) return response()->json(['message' => 'Unauthorized.'], 403);

        $requisitions = Requisition::with('product')
            ->where('van_rep_id', $user->id)
            ->orderByDesc('date_requested')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'product' => [
                    'id' => $r->product_id,
                    'name' => $r->product->name ?? 'Unknown',
                    'unit' => $r->product->unit ?? '',
                ],
                'quantity' => $r->quantity,
                'status' => $r->status,
                'date_requested' => $r->date_requested->toDateString(),
            ]);

        return response()->json(['requisitions' => $requisitions]);
    }

    // Get van capacity info
    public function getCapacityInfo()
    {
        $user = auth()->user();
        if (!$user || !$user->isRep()) return response()->json(['message' => 'Unauthorized.'], 403);

        $total = $user->van_capacity ?? 0;
        $used = $user->getCurrentVanCapacity();
        $available = max($total - $used, 0);

        return response()->json([
            'capacity_info' => [
                'total_capacity' => $total,
                'used_capacity' => $used,
                'available_capacity' => $available
            ]
        ]);
    }
}
