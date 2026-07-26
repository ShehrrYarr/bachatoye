<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Single source of truth for revenue/COGS/profit math shared by the Sales
 * and Profit & Loss reports, so the two can never diverge again.
 *
 * Expects each Order in $orders to have 'items.returnItems.returnOrder'
 * eager loaded (and 'items.product.category' when filtering by section).
 */
class OrderProfitCalculator
{
    public static function summarize(Collection $orders, ?int $sectionId = null, ?int $categoryId = null): array
    {
        $grossRevenue = 0.0;
        $totalRefunds = 0.0;
        $totalCogs    = 0.0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!self::itemMatchesFilter($item, $sectionId, $categoryId)) {
                    continue;
                }

                [$refund, $restockedQty] = self::itemReturns($item);

                $qty  = (int) $item->quantity;
                $unit = (float) $item->unit_price;
                $cost = (float) $item->cost_price;

                $grossRevenue += $unit * $qty;
                $totalRefunds += $refund;
                $totalCogs    += $cost * max(0, $qty - $restockedQty);
            }
        }

        $netRevenue = $grossRevenue - $totalRefunds;

        return [
            'grossRevenue' => $grossRevenue,
            'totalRefunds' => $totalRefunds,
            'netRevenue'   => $netRevenue,
            'totalCogs'    => $totalCogs,
            'profit'       => $netRevenue - $totalCogs,
        ];
    }

    /**
     * Per-item profit, netting out approved/completed returns against it —
     * used by the Sales report's per-order breakdown table.
     *
     * @return array{revenue: float, refund: float, cogs: float, profit: float}
     */
    public static function itemProfit($item): array
    {
        [$refund, $restockedQty] = self::itemReturns($item);

        $qty  = (int) $item->quantity;
        $unit = (float) $item->unit_price;
        $cost = (float) $item->cost_price;

        $revenue = $unit * $qty - $refund;
        $cogs    = $cost * max(0, $qty - $restockedQty);

        return [
            'revenue' => $revenue,
            'refund'  => $refund,
            'cogs'    => $cogs,
            'profit'  => $revenue - $cogs,
        ];
    }

    /**
     * @return array{0: float, 1: int} [total refunded, qty returned-and-restocked]
     */
    private static function itemReturns($item): array
    {
        $returnItems = $item->relationLoaded('returnItems') ? $item->returnItems : collect();

        $approved = $returnItems->filter(
            fn($ri) => in_array($ri->returnOrder?->status, ['approved', 'completed'], true)
        );

        $refund       = (float) $approved->sum('refund_amount');
        $restockedQty = (int) $approved->filter(fn($ri) => (bool) $ri->returnOrder?->restock)->sum('quantity');

        return [$refund, $restockedQty];
    }

    private static function itemMatchesFilter($item, ?int $sectionId, ?int $categoryId): bool
    {
        if (!$sectionId && !$categoryId) {
            return true;
        }

        $product = $item->product;
        if (!$product) {
            return false;
        }

        if ($sectionId) {
            return (int) ($product->category?->section_id) === $sectionId;
        }

        return (int) $product->category_id === $categoryId || (int) $product->subcategory_id === $categoryId;
    }
}
