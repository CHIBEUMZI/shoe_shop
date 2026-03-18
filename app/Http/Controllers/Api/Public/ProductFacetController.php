<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductFacetController extends Controller
{
    public function index(Request $request)
    {
        $base = Product::query()
            ->where('status', 1)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->query('search'));
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                        ->orWhere('slug', 'like', "%{$s}%")
                        ->orWhere('sku', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('featured'), function ($q) use ($request) {
                $q->where('is_featured', (int) $request->query('featured') === 1);
            })
            ->when($request->filled('brand'), function ($q) use ($request) {
                $brands = (array) $request->query('brand');
                $brands = array_values(array_filter(array_map('intval', $brands)));
                if (count($brands)) $q->whereIn('brand_id', $brands);
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $cats = (array) $request->query('category');
                $cats = array_values(array_filter(array_map('intval', $cats)));
                if (count($cats)) {
                    $q->whereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $cats));
                }
            })
            ->when($request->filled('size'), function ($q) use ($request) {
                $sizes = (array) $request->query('size');
                $sizes = array_values(array_filter(array_map('strval', $sizes)));
                if (count($sizes)) {
                    $q->whereHas('variants', fn ($vq) => $vq->where('is_active', 1)->whereIn('size', $sizes));
                }
            })
            ->when($request->filled('color'), function ($q) use ($request) {
                $colors = (array) $request->query('color');
                $colors = array_values(array_filter(array_map('strval', $colors)));
                if (count($colors)) {
                    $q->whereHas('variants', fn ($vq) => $vq->where('is_active', 1)->whereIn('color', $colors));
                }
            })
            ->when($request->filled('price_ranges'), function ($q) use ($request) {
                $ranges = $request->query('price_ranges');
                $ranges = is_array($ranges) ? $ranges : [$ranges];

                $map = [
                    'lt500'  => [null, 500000],
                    '500-1m' => [500000, 1000000],
                    '1-3m'   => [1000000, 3000000],
                    'gt3m'   => [3000000, null],
                ];

                $picked = null;
                foreach ($ranges as $r) {
                    $r = (string) $r;
                    if (isset($map[$r])) { $picked = $r; break; }
                }
                if (!$picked) return;

                [$min, $max] = $map[$picked];

                if ($min !== null) $q->whereRaw('COALESCE(base_sale_price, base_price) >= ?', [(int) $min]);
                if ($max !== null) $q->whereRaw('COALESCE(base_sale_price, base_price) <= ?', [(int) $max]);
            })
            ->when(
                !$request->filled('price_ranges') && ($request->filled('price_min') || $request->filled('price_max')),
                function ($q) use ($request) {
                    $min = $request->filled('price_min') ? (int) $request->query('price_min') : null;
                    $max = $request->filled('price_max') ? (int) $request->query('price_max') : null;

                    if ($min !== null) $q->whereRaw('COALESCE(base_sale_price, base_price) >= ?', [$min]);
                    if ($max !== null) $q->whereRaw('COALESCE(base_sale_price, base_price) <= ?', [$max]);
                }
            );

        $productIds = (clone $base)->select('id')->pluck('id');

        if ($productIds->isEmpty()) {
            return response()->json([
                'brands' => [],
                'categories' => [],
                'sizes' => [],
                'colors' => [],
                'price' => ['min' => 0, 'max' => 0],
            ]);
        }
        $brands = DB::table('products')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->whereIn('products.id', $productIds)
            ->select('brands.id', 'brands.name', 'brands.slug', DB::raw('COUNT(*) as count'))
            ->groupBy('brands.id', 'brands.name', 'brands.slug')
            ->orderBy('brands.name')
            ->get();
        $categories = DB::table('category_product')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->whereIn('category_product.product_id', $productIds)
            ->select(
                'categories.id',
                'categories.name',
                'categories.slug',
                DB::raw('COUNT(DISTINCT category_product.product_id) as count')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->orderBy('categories.name')
            ->get();
        $sizes = DB::table('product_variants')
            ->where('is_active', 1)
            ->whereIn('product_id', $productIds)
            ->whereNotNull('size')
            ->select('size as value', DB::raw('COUNT(DISTINCT product_id) as count'))
            ->groupBy('size')
            ->orderByRaw('CAST(size AS UNSIGNED) ASC')
            ->get();
        $colors = DB::table('product_variants')
            ->where('is_active', 1)
            ->whereIn('product_id', $productIds)
            ->whereNotNull('color')
            ->select('color as value', DB::raw('COUNT(DISTINCT product_id) as count'))
            ->groupBy('color')
            ->orderBy('color')
            ->get();
        $price = DB::table('products')
            ->whereIn('id', $productIds)
            ->selectRaw('MIN(COALESCE(base_sale_price, base_price)) as min')
            ->selectRaw('MAX(COALESCE(base_sale_price, base_price)) as max')
            ->first();

        return response()->json([
            'brands' => $brands,
            'categories' => $categories,
            'sizes' => $sizes,
            'colors' => $colors,
            'price' => [
                'min' => (int) ($price->min ?? 0),
                'max' => (int) ($price->max ?? 0),
            ],
        ]);
    }
}