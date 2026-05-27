<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BarcodeService
{
    /**
     * Generate the next sequential barcode for a product.
     *
     * If the category belongs to a section that has a barcode_prefix set,
     * the format is: {PREFIX}-{NNNN}  e.g. MOB-0042
     * Otherwise falls back to a plain incrementing numeric string.
     */
    public static function generate(?int $categoryId = null): string
    {
        $prefix = null;

        if ($categoryId) {
            $category = Category::with('section')->find($categoryId);
            if ($category && $category->section && $category->section->barcode_prefix) {
                $prefix = strtoupper(trim($category->section->barcode_prefix));
            }
        }

        if ($prefix) {
            $pattern = $prefix . '-%';
            $max = Product::whereNotNull('barcode')
                          ->where('barcode', 'like', $pattern)
                          ->get()
                          ->map(fn($p) => (int) substr($p->barcode, strlen($prefix) + 1))
                          ->max();
            $next = ($max ?? 0) + 1;
            return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
        }

        // Global numeric fallback
        $max = Product::whereNotNull('barcode')
                      ->whereRaw("barcode REGEXP '^[0-9]+$'")
                      ->max(DB::raw('CAST(barcode AS UNSIGNED)'));

        $next = ($max ?? 0) + 1;
        return str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
