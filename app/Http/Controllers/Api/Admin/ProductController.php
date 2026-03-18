<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $query = Product::query()
            ->with(['categories', 'variants.images'])
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', '%'.$request->search.'%')
            )
            ->when(isset($request->status), fn ($q) =>
                $q->where('status', (int)$request->status)
            )
            ->latest();

        return ProductResource::collection($query->paginate($perPage));
    }

    public function show(Product $product)
    {
        $product->load(['categories', 'variants.images']);
        return new ProductResource($product);
    }

    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($data) {
            $product = Product::create([
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'sku' => $data['sku'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'status' => $data['status'],
                'is_featured' => $data['is_featured'] ?? false,
            ]);

            if (!empty($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }

            foreach ($data['variants'] as $v) {
                $variant = $product->variants()->create([
                    'color' => $v['color'],
                    'size' => $v['size'],
                    'sku' => $v['sku'] ?? null,
                    'price' => $v['price'],
                    'sale_price' => $v['sale_price'] ?? null,
                    'stock' => $v['stock'],
                    'is_active' => $v['is_active'] ?? true,
                ]);

                foreach (($v['images'] ?? []) as $img) {
                    $variant->images()->create([
                        'url' => $img['url'],
                        'sort_order' => $img['sort_order'] ?? 0,
                    ]);
                }
            }

            // base price = min variant price
            $minPrice = $product->variants()->min('price');
            $minSale  = $product->variants()->whereNotNull('sale_price')->min('sale_price');

            $product->update([
                'base_price' => $minPrice,
                'base_sale_price' => $minSale,
            ]);

            return $product;
        });

        $product->load(['categories', 'variants.images']);
        return new ProductResource($product);
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($data, $product) {
            $product->update([
                'brand_id' => $data['brand_id'] ?? $product->brand_id,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'sku' => $data['sku'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'status' => $data['status'],
                'is_featured' => $data['is_featured'] ?? $product->is_featured,
            ]);

            if (array_key_exists('category_ids', $data)) {
                $product->categories()->sync($data['category_ids'] ?? []);
            }

            $keepIds = [];

            foreach ($data['variants'] as $v) {
                if (!empty($v['id'])) {
                    $variant = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->where('id', $v['id'])
                        ->firstOrFail();

                    $variant->update([
                        'color' => $v['color'],
                        'size' => $v['size'],
                        'sku' => $v['sku'] ?? null,
                        'price' => $v['price'],
                        'sale_price' => $v['sale_price'] ?? null,
                        'stock' => $v['stock'],
                        'is_active' => $v['is_active'] ?? $variant->is_active,
                    ]);
                } else {
                    $variant = $product->variants()->create([
                        'color' => $v['color'],
                        'size' => $v['size'],
                        'sku' => $v['sku'] ?? null,
                        'price' => $v['price'],
                        'sale_price' => $v['sale_price'] ?? null,
                        'stock' => $v['stock'],
                        'is_active' => $v['is_active'] ?? true,
                    ]);
                }

                $keepIds[] = $variant->id;

                // images: nếu gửi images thì replace
                if (array_key_exists('images', $v)) {
                    $variant->images()->delete();
                    foreach (($v['images'] ?? []) as $img) {
                        $variant->images()->create([
                            'url' => $img['url'],
                            'sort_order' => $img['sort_order'] ?? 0,
                        ]);
                    }
                }
            }

            // xoá variant không còn trong payload
            $product->variants()->whereNotIn('id', $keepIds)->delete();

            $minPrice = $product->variants()->min('price');
            $minSale  = $product->variants()->whereNotNull('sale_price')->min('sale_price');

            $product->update([
                'base_price' => $minPrice,
                'base_sale_price' => $minSale,
            ]);

            return $product;
        });

        $product->load(['categories', 'variants.images']);
        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
