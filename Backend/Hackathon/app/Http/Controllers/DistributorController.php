<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DistributorStock;
use App\Models\Requisition;
use App\Models\VanStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DistributorController extends Controller
{
    /**
     * Get all pending requisitions for the distributor
     */
    public function getPendingRequisitions(Request $request)
    {
        $user = $request->user();

        if (!$user->isDistributor()) {
            return response()->json(['message' => 'Unauthorized. Only distributors can access this resource.'], 403);
        }

        $requisitions = Requisition::with(['vanRep', 'product'])
            ->where('distributor_id', $user->id)
            ->where('status', Requisition::STATUS_PENDING)
            ->orderBy('date_requested', 'desc')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'van_rep' => ['id' => $req->vanRep->id, 'name' => $req->vanRep->name],
                    'product' => ['id' => $req->product->id, 'name' => $req->product->name, 'unit' => $req->product->unit],
                    'quantity' => $req->quantity,
                    'date_requested' => $req->date_requested,
                    'status' => $req->status
                ];
            });

        return response()->json(['pending_requisitions' => $requisitions]);
    }

    /**
     * Approve a requisition
     */
    public function approveRequisition(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isDistributor()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $requisition = Requisition::findOrFail($id);

        if ($requisition->distributor_id !== $user->id) {
            return response()->json(['message' => 'Requisition not assigned to you.'], 403);
        }

        if ($requisition->status !== Requisition::STATUS_PENDING) {
            return response()->json(['message' => 'Requisition already processed.'], 400);
        }

        $stock = DistributorStock::where('user_id', $user->id)
            ->where('product_id', $requisition->product_id)
            ->first();

        if (!$stock || $stock->quantity < $requisition->quantity) {
            return response()->json([
                'message' => 'Insufficient stock to approve requisition.',
                'available_stock' => $stock?->quantity ?? 0,
                'requested_quantity' => $requisition->quantity
            ], 400);
        }

        // Deduct stock from distributor and add to van
        $stock->quantity -= $requisition->quantity;
        $stock->save();

        $vanStock = VanStock::firstOrCreate(
            ['user_id' => $requisition->van_rep_id, 'product_id' => $requisition->product_id],
            ['quantity' => 0]
        );
        $vanStock->quantity += $requisition->quantity;
        $vanStock->save();

        // Update requisition
        $requisition->status = Requisition::STATUS_APPROVED;
        $requisition->approved_by = $user->id;
        $requisition->approved_at = Carbon::now();
        $requisition->save();

        return response()->json([
            'message' => 'Requisition approved successfully.',
            'requisition_id' => $requisition->id
        ]);
    }

    /**
     * Reject a requisition
     */
    public function rejectRequisition(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isDistributor()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $requisition = Requisition::findOrFail($id);

        if ($requisition->distributor_id !== $user->id) {
            return response()->json(['message' => 'Requisition not assigned to you.'], 403);
        }

        if ($requisition->status !== Requisition::STATUS_PENDING) {
            return response()->json(['message' => 'Requisition already processed.'], 400);
        }

        $requisition->status = Requisition::STATUS_REJECTED;
        $requisition->approved_by = $user->id;
        $requisition->approved_at = Carbon::now();
        $requisition->save();

        return response()->json([
            'message' => 'Requisition rejected successfully.',
            'requisition_id' => $requisition->id
        ]);
    }

    /**
     * View distributor stock
     */
    public function getStock(Request $request)
    {
        $user = $request->user();
        if (!$user->isDistributor()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $stock = DistributorStock::with('product')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($s) => [
                'product_id' => $s->product_id,
                'product_name' => $s->product->name,
                'quantity' => $s->quantity
            ]);

        return response()->json(['stock' => $stock]);
    }
}
