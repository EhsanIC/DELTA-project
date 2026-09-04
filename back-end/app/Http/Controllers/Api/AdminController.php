<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Expense;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Return the current settings, including defaults for missing rows.
     */
    public function settings(): JsonResponse
    {
        return response()->json([
            'settings' => Setting::values(),
        ]);
    }

    /**
     * Update one or more supported admin settings.
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            Setting::setValues($request->validated('settings'));
        });

        return response()->json([
            'settings' => Setting::values(),
        ]);
    }

    /**
     * Return management KPIs calculated from the current business state.
     */
    public function dashboard(): JsonResponse
    {
        $settings = Setting::values();
        $opportunities = Opportunity::query()->with('product')->orderBy('id')->get();
        $openStages = ['New', 'Quoted'];
        $openOpportunities = $opportunities->whereIn('stage', $openStages);
        $wonOpportunities = $opportunities->where('stage', 'Won');

        $wonRevenue = 0.0;
        $wonProfit = 0.0;
        $requiredInstallHours = 0.0;
        $atRiskOpportunities = [];
        $inventoryShortageCount = 0;
        $marginRiskCount = 0;

        foreach ($opportunities as $opportunity) {
            $revenue = round($opportunity->qty * (float) $opportunity->unit_price, 2);
            $costOfGoods = round($opportunity->qty * (float) $opportunity->product->unit_cost, 2);
            $shippingCost = round(
                (float) $settings['fixed_shipping_cost']
                + ((float) $settings['per_unit_shipping_cost'] * $opportunity->qty),
                2,
            );
            $profit = round($revenue - $costOfGoods - $shippingCost, 2);
            $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0;
            $freeInventory = $opportunity->product->physical_inventory
                - $opportunity->product->reserved_inventory;
            $installHours = round(
                ($opportunity->qty * $opportunity->product->install_minutes_per_unit) / 60,
                2,
            );

            if ($opportunity->stage === 'Won') {
                $wonRevenue += $revenue;
                $wonProfit += $profit;
                $requiredInstallHours += $installHours;
            }

            if (in_array($opportunity->stage, $openStages, true)) {
                $hasInventoryRisk = $freeInventory < $opportunity->qty;
                $hasMarginRisk = $margin < (float) $settings['target_margin'];

                if ($hasInventoryRisk) {
                    $inventoryShortageCount++;
                }

                if ($hasMarginRisk) {
                    $marginRiskCount++;
                }

                if ($hasInventoryRisk || $hasMarginRisk) {
                    $atRiskOpportunities[] = [
                        'id' => $opportunity->id,
                        'product_id' => $opportunity->product_id,
                        'stage' => $opportunity->stage,
                        'revenue' => number_format($revenue, 2, '.', ''),
                        'margin_percent' => number_format($margin, 2, '.', ''),
                        'free_inventory' => $freeInventory,
                        'qty' => $opportunity->qty,
                        'risks' => array_values(array_filter([
                            $hasInventoryRisk ? 'inventory' : null,
                            $hasMarginRisk ? 'margin' : null,
                        ])),
                    ];
                }
            }
        }

        $receipts = (float) Receipt::query()->sum('amount');
        $payments = (float) Payment::query()->sum('amount');
        $expenses = (float) Expense::query()->sum('amount');
        $cashBalance = round($receipts - $payments - $expenses, 2);
        $capacityHours = (float) $settings['available_capacity_hours'];
        $capacityUtilization = $capacityHours > 0
            ? round(($requiredInstallHours / $capacityHours) * 100, 2)
            : 0.0;
        $capacitySeverity = $capacityUtilization >= (float) $settings['capacity_critical_threshold_percent']
            ? 'critical'
            : ($capacityUtilization >= (float) $settings['capacity_risk_threshold_percent']
                ? 'risk'
                : ($capacityUtilization >= (float) $settings['capacity_info_threshold_percent'] ? 'info' : null));

        $criticalAlerts = 0;
        $riskAlerts = 0;
        $infoAlerts = 0;

        if ($settings['alerts_inventory_enabled']) {
            $criticalAlerts += $inventoryShortageCount;
        }
        if ($settings['alerts_cash_enabled'] && $cashBalance < (float) $settings['minimum_operating_cash']) {
            $criticalAlerts++;
        }
        if ($settings['alerts_margin_enabled']) {
            $riskAlerts += $marginRiskCount;
        }
        if ($settings['alerts_capacity_enabled'] && $capacitySeverity !== null) {
            match ($capacitySeverity) {
                'critical' => $criticalAlerts++,
                'risk' => $riskAlerts++,
                'info' => $infoAlerts++,
            };
        }

        return response()->json([
            'dashboard' => [
                'firm_revenue' => number_format($wonRevenue, 2, '.', ''),
                'operating_profit' => number_format($wonProfit, 2, '.', ''),
                'cash_balance' => number_format($cashBalance, 2, '.', ''),
                'open_opportunities' => $openOpportunities->count(),
                'won_opportunities' => $wonOpportunities->count(),
                'at_risk_opportunities' => count($atRiskOpportunities),
                'critical_alerts' => $criticalAlerts,
                'inventory' => Product::query()->orderBy('id')->get()->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'physical_inventory' => $product->physical_inventory,
                    'reserved_inventory' => $product->reserved_inventory,
                    'free_inventory' => $product->physical_inventory - $product->reserved_inventory,
                    'safety_stock' => $product->safety_stock,
                ])->values(),
                'capacity' => [
                    'available_hours' => number_format($capacityHours, 2, '.', ''),
                    'required_install_hours' => number_format($requiredInstallHours, 2, '.', ''),
                    'utilization_percent' => number_format($capacityUtilization, 2, '.', ''),
                ],
                'cash' => [
                    'receipts' => number_format($receipts, 2, '.', ''),
                    'payments' => number_format($payments, 2, '.', ''),
                    'expenses' => number_format($expenses, 2, '.', ''),
                ],
                'alert_counts' => [
                    'info' => $infoAlerts,
                    'risk' => $riskAlerts,
                    'critical' => $criticalAlerts,
                ],
                'at_risk' => $atRiskOpportunities,
            ],
        ]);
    }
}
