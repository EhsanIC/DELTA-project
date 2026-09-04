<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Opportunity;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OpportunityController extends Controller
{
    /**
     * List opportunities with their product and current calculations.
     */
    public function index(): JsonResponse
    {
        $opportunities = Opportunity::query()
            ->with('product')
            ->orderBy('id')
            ->get()
            ->map(fn (Opportunity $opportunity): array => $this->payload($opportunity))
            ->values();

        return response()->json([
            'opportunities' => $opportunities,
        ]);
    }

    /**
     * Create an opportunity and reserve inventory when it is immediately won.
     */
    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $opportunity = DB::transaction(function () use ($request): Opportunity {
            $opportunity = Opportunity::query()->create($request->validated());

            if ($opportunity->stage === 'Won') {
                $this->changeReservedInventory(
                    productId: $opportunity->product_id,
                    quantity: $opportunity->qty,
                );
            }

            return $opportunity;
        });

        return response()->json([
            'opportunity' => $this->payload($opportunity->load('product')),
        ], 201);
    }

    /**
     * Update an opportunity and keep Won inventory reservations synchronized.
     */
    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        $opportunity = DB::transaction(function () use ($request, $opportunity): Opportunity {
            $current = Opportunity::query()
                ->lockForUpdate()
                ->findOrFail($opportunity->id);
            $previousProductId = $current->product_id;
            $previousQuantity = $current->qty;
            $previousStage = $current->stage;

            if ($previousStage === 'Won') {
                $this->changeReservedInventory(
                    productId: $previousProductId,
                    quantity: -$previousQuantity,
                );
            }

            $current->fill($request->validated());
            $current->save();

            if ($current->stage === 'Won') {
                $this->changeReservedInventory(
                    productId: $current->product_id,
                    quantity: $current->qty,
                );
            }

            return $current;
        });

        return response()->json([
            'opportunity' => $this->payload($opportunity->load('product')),
        ]);
    }

    /**
     * Apply a reservation delta without blocking saves for insufficient stock.
     */
    private function changeReservedInventory(int $productId, int $quantity): void
    {
        Product::query()
            ->lockForUpdate()
            ->whereKey($productId)
            ->increment('reserved_inventory', $quantity);
    }

    /**
     * Build the public opportunity payload and its basic sales calculations.
     *
     * @return array<string, mixed>
     */
    private function payload(Opportunity $opportunity): array
    {
        $quantity = $opportunity->qty;
        $unitPrice = (float) $opportunity->unit_price;
        $unitCost = (float) $opportunity->product->unit_cost;
        $revenue = round($quantity * $unitPrice, 2);
        $costOfGoods = round($quantity * $unitCost, 2);
        $operatingProfit = round($revenue - $costOfGoods, 2);
        $marginPercent = $revenue > 0
            ? round(($operatingProfit / $revenue) * 100, 2)
            : 0.0;

        return [
            'id' => $opportunity->id,
            'product_id' => $opportunity->product_id,
            'qty' => $quantity,
            'unit_price' => $opportunity->unit_price,
            'due_date' => $opportunity->due_date?->format('Y-m-d'),
            'stage' => $opportunity->stage,
            'product' => [
                'id' => $opportunity->product->id,
                'name' => $opportunity->product->name,
                'unit_cost' => $opportunity->product->unit_cost,
                'physical_inventory' => $opportunity->product->physical_inventory,
                'reserved_inventory' => $opportunity->product->reserved_inventory,
                'free_inventory' => $opportunity->product->physical_inventory
                    - $opportunity->product->reserved_inventory,
            ],
            'revenue' => number_format($revenue, 2, '.', ''),
            'cost_of_goods' => number_format($costOfGoods, 2, '.', ''),
            'operating_profit' => number_format($operatingProfit, 2, '.', ''),
            'margin_percent' => number_format($marginPercent, 2, '.', ''),
        ];
    }
}
