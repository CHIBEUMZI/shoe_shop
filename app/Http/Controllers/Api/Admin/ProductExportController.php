<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ProductExportController extends Controller
{
    public function excel(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        return Excel::download(
            new ProductsExport($filters),
            'danh-sach-san-pham-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function pdf(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $query = Product::query()
            ->with(['categories', 'variants'])
            ->when(!empty($filters['search']), fn ($q) =>
                $q->where('name', 'like', '%' . $filters['search'] . '%')
            )
            ->when(isset($filters['status']), fn ($q) =>
                $q->where('status', (int) $filters['status'])
            )
            ->orderBy('created_at', 'desc');

        $products = $query->get();

        $inventory = collect();
        $totalStock = 0;
        $totalVariants = 0;
        $lowStockVariants = 0;

        foreach ($products as $product) {
            $categories = $product->categories->pluck('name')->implode(', ');
            $variants = $product->variants->sortBy('color')->sortBy('size');

            $isFirstVariant = true;
            foreach ($variants as $variant) {
                $totalStock += $variant->stock;
                $totalVariants++;

                if ($variant->stock > 0 && $variant->stock <= 5) {
                    $lowStockVariants++;
                }

                $stockClass = '';
                if ($variant->stock === 0) {
                    $stockClass = 'stock-out';
                } elseif ($variant->stock <= 5) {
                    $stockClass = 'stock-low';
                }

                $inventory->push([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '-',
                    'categories' => $categories ?: '-',
                    'color' => $variant->color,
                    'color_hex' => $variant->color_hex ?? '',
                    'size' => $variant->size,
                    'variant_sku' => $variant->sku ?? '-',
                    'variant_price' => $variant->price,
                    'variant_sale_price' => $variant->sale_price,
                    'stock' => $variant->stock,
                    'is_active' => $variant->is_active,
                    'is_first_variant' => $isFirstVariant,
                    'stock_class' => $stockClass,
                ]);

                $isFirstVariant = false;
            }

            if ($variants->isEmpty()) {
                $inventory->push([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '-',
                    'categories' => $categories ?: '-',
                    'color' => '-',
                    'color_hex' => '',
                    'size' => '-',
                    'variant_sku' => '-',
                    'variant_price' => $product->base_price,
                    'variant_sale_price' => $product->base_sale_price,
                    'stock' => 0,
                    'is_active' => $product->status === 1,
                    'is_first_variant' => true,
                    'stock_class' => 'stock-out',
                ]);
            }
        }

        $data = [
            'inventory' => $inventory,
            'date' => now()->format('d/m/Y H:i:s'),
            'totalProducts' => $products->count(),
            'totalVariants' => $totalVariants,
            'totalStock' => $totalStock,
            'lowStockVariants' => $lowStockVariants,
        ];

        $pdf = Pdf::loadView('exports.products', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('ton-kho-san-pham-' . now()->format('Y-m-d-His') . '.pdf');
    }
}
