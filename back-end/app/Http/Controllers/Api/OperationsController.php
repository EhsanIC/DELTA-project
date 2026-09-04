<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCapacityAdjustmentRequest;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Models\CapacityAdjustment;
use App\Models\InventoryAdjustment;
use App\Models\Opportunity;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    /**
     * Apply an inventory adjustment and return the affected opportunities.
     */
    public function adjustInventory(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $inventoryAdjustment = DB::transaction(function () use ($request): InventoryAdjustment {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            Product::query()
                ->lockForUpdate()
                ->whereKey($data['product_id'])
                ->update(['physical_inventory' => $data['new_quantity']]);

            return InventoryAdjustment::query()->create($data);
        });

        $affectedOpportunities = Opportunity::query()
            ->with('product')
            ->where('product_id', $inventoryAdjustment->product_id)
            ->whereIn('stage', ['New', 'Quoted', 'Won'])
            ->orderBy('id')
            ->get()
            ->values();

        return response()->json([
            'inventory_adjustment' => $inventoryAdjustment->load('product'),
            'affected_opportunities' => $affectedOpportunities,
        ], 201);
    }

    /**
     * Record a capacity adjustment and return opportunities due on that date.
     */
    public function adjustCapacity(StoreCapacityAdjustmentRequest $request): JsonResponse
    {
        $capacityAdjustment = CapacityAdjustment::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $affectedOpportunities = Opportunity::query()
            ->with('product')
            ->whereDate('due_date', $capacityAdjustment->date)
            ->whereIn('stage', ['New', 'Quoted', 'Won'])
            ->orderBy('id')
            ->get()
            ->values();

        return response()->json([
            'capacity_adjustment' => $capacityAdjustment->load('user'),
            'affected_opportunities' => $affectedOpportunities,
        ], 201);
    }
}
