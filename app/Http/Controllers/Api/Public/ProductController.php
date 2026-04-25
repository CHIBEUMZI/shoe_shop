<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 12);

        $query = Product::query()
            ->where('status', 1)
            ->with([
                'brand:id,name,slug',
                'categories:id,name,slug',
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->query('search'));
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                        ->orWhere('slug', 'like', "%{$s}%")
                        ->orWhere('sku', 'like', "%{$s}%")
                        ->orWhere('short_description', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
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
                    $q->whereHas('categories', function ($cq) use ($cats) {
                        $cq->whereIn('categories.id', $cats);
                    });
                }
            })

            ->when($request->filled('size'), function ($q) use ($request) {
                $sizes = (array) $request->query('size');
                $sizes = array_values(array_filter(array_map('strval', $sizes)));
                if (count($sizes)) {
                    $q->whereHas('variants', function ($vq) use ($sizes) {
                        $vq->where('is_active', 1)->whereIn('size', $sizes);
                    });
                }
            })

            ->when($request->filled('color'), function ($q) use ($request) {
                $colors = (array) $request->query('color');
                $colors = array_values(array_filter(array_map('strval', $colors)));
                if (count($colors)) {
                    $q->whereHas('variants', function ($vq) use ($colors) {
                        $vq->where('is_active', 1)->whereIn('color', $colors);
                    });
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

                $key = null;
                foreach ($ranges as $r) {
                    $r = (string) $r;
                    if (isset($map[$r])) { $key = $r; break; }
                }

                if (!$key) return;

                [$min, $max] = $map[$key];

                if ($min !== null) {
                    $q->whereRaw('COALESCE(base_sale_price, base_price) >= ?', [(int) $min]);
                }
                if ($max !== null) {
                    $q->whereRaw('COALESCE(base_sale_price, base_price) <= ?', [(int) $max]);
                }
            })

            ->when(
                !$request->filled('price_ranges') && ($request->filled('price_min') || $request->filled('price_max')),
                function ($q) use ($request) {
                    $min = $request->filled('price_min') ? (int) $request->query('price_min') : null;
                    $max = $request->filled('price_max') ? (int) $request->query('price_max') : null;

                    if ($min !== null) {
                        $q->whereRaw('COALESCE(base_sale_price, base_price) >= ?', [$min]);
                    }
                    if ($max !== null) {
                        $q->whereRaw('COALESCE(base_sale_price, base_price) <= ?', [$max]);
                    }
                }
            )

            ->when($request->filled('featured'), function ($q) use ($request) {
                $q->where('is_featured', (int) $request->query('featured') === 1);
            })

            // =============================================
            // STYLE FILTER - Hỗ trợ occasion-based search
            // =============================================
            ->when($request->filled('style'), function ($q) use ($request) {
                $styles = (array) $request->query('style');
                $styles = array_values(array_filter(array_map('strval', $styles)));
                if (count($styles)) {
                    $q->where(function ($qq) use ($styles) {
                        foreach ($styles as $style) {
                            $qq->orWhere(function ($q1) use ($style) {
                                $style = trim(strtolower($style));
                                
                                // Map style keywords to categories/products
                                $styleMap = [
                                    'thời trang' => ['sneaker', 'fashion', 'thời trang'],
                                    'sang trọng' => ['lịch sự', 'formal', 'premium'],
                                    'lịch sự' => ['lịch sự', 'formal', 'công sở'],
                                    'casual' => ['casual', 'thoải mái', 'đời thường'],
                                    'thể thao' => ['thể thao', 'sport', 'năng động'],
                                    'lãng mạn' => ['romantic', 'valentine', 'đỏ', 'hồng'],
                                    'năng động' => ['sporty', 'năng động', 'trẻ trung'],
                                    'đi bộ' => ['walking', 'đi bộ', 'comfort'],
                                ];
                                
                                $mappedTerms = $styleMap[$style] ?? [$style];
                                
                                $q1->where(function ($q2) use ($mappedTerms) {
                                    foreach ($mappedTerms as $term) {
                                        $q2->orWhere('name', 'like', "%{$term}%")
                                           ->orWhere('short_description', 'like', "%{$term}%")
                                           ->orWhere('description', 'like', "%{$term}%");
                                    }
                                });
                            });
                        }
                    });
                }
            })

            // =============================================
            // OCCASION FILTER - Tìm giày theo dịp sử dụng
            // =============================================
            ->when($request->filled('occasion'), function ($q) use ($request) {
                $occasions = (array) $request->query('occasion');
                $occasions = array_values(array_filter(array_map('strval', $occasions)));
                if (count($occasions)) {
                    $q->where(function ($qq) use ($occasions) {
                        foreach ($occasions as $occasion) {
                            $qq->orWhere(function ($q1) use ($occasion) {
                                $occasion = trim(strtolower($occasion));
                                
                                // Occasion to search terms mapping
                                $occasionMap = [
                                    'valentine' => ['valentine', 'lãng mạn', 'hồng', 'đỏ', 'tình nhân'],
                                    'interview' => ['phỏng vấn', 'công sở', 'văn phòng', 'lịch sự', 'formal'],
                                    'casual' => ['casual', 'dạo phố', 'đi chơi', 'thoải mái', 'cuối tuần'],
                                    'travel' => ['du lịch', 'phượt', 'travel', 'đi bộ nhiều'],
                                    'party' => ['party', 'tiệc', 'club', 'sang trọng', 'nổi bật'],
                                    'football' => ['đá bóng', 'bóng đá', 'football', 'sân cỏ', 'fg', 'tf', 'sg', 'mercurial', 'predator', 'copa'],
                                    'running' => ['chạy bộ', 'running', 'jogging', 'marathon', 'cardio'],
                                    'gym' => ['gym', 'tập gym', 'crossfit', 'fitness', 'nâng tạ', 'workout', 'tập luyện'],
                                    'sports' => ['thể thao', 'sport', 'năng động'],
                                ];
                                
                                $terms = $occasionMap[$occasion] ?? [$occasion];
                                
                                $q1->where(function ($q2) use ($terms) {
                                    foreach ($terms as $term) {
                                        $q2->orWhere('name', 'like', "%{$term}%")
                                           ->orWhere('short_description', 'like', "%{$term}%")
                                           ->orWhere('description', 'like', "%{$term}%");
                                    }
                                });
                            });
                        }
                    });
                }
            });

        $sort = (string) $request->query('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderByRaw('COALESCE(base_sale_price, base_price) asc');
        } elseif ($sort === 'price_desc') {
            $query->orderByRaw('COALESCE(base_sale_price, base_price) desc');
        } elseif ($sort === 'popular') {
            $query->orderByDesc('views');
        } else {
            $query->latest();
        }

        return ProductResource::collection($query->paginate($perPage));
    }

    public function show(string $slug)
    {
        $product = Product::query()
            ->where('status', 1)
            ->where('slug', $slug)
            ->with([
                'brand:id,name,slug',
                'categories:id,name,slug',
                'variants' => function ($q) {
                    $q->where('is_active', 1)->orderBy('id');
                },
                'variants.images' => function ($q) {
                    $q->orderBy('sort_order');
                },
            ])
            ->firstOrFail();

        $product->increment('views');

        return new ProductResource($product);
    }
}