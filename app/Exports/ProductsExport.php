<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidth;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    protected array $filters = [];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Product::query()
            ->with(['categories', 'variants.images'])
            ->when(!empty($this->filters['search']), fn ($q) =>
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
            )
            ->when(isset($this->filters['status']), fn ($q) =>
                $q->where('status', (int) $this->filters['status'])
            )
            ->orderBy('created_at', 'desc');

        $products = $query->get();

        $rows = collect();
        foreach ($products as $product) {
            $categories = $product->categories->pluck('name')->implode(', ');
            $variants = $product->variants->sortBy('color')->sortBy('size');

            if ($variants->isEmpty()) {
                $rows->push([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '-',
                    'categories' => $categories ?: '-',
                    'base_price' => $product->base_price,
                    'base_sale_price' => $product->base_sale_price,
                    'color' => '-',
                    'color_hex' => '',
                    'size' => '-',
                    'variant_sku' => '-',
                    'variant_price' => '-',
                    'variant_sale_price' => '-',
                    'stock' => 0,
                    'is_active' => $product->status === 1,
                    'is_featured' => $product->is_featured,
                    'created_at' => $product->created_at->format('d/m/Y'),
                    'stock_level' => 'out', // for styling
                ]);
            } else {
                foreach ($variants as $variant) {
                    $stockLevel = 'normal';
                    if ($variant->stock === 0) {
                        $stockLevel = 'out';
                    } elseif ($variant->stock <= 5) {
                        $stockLevel = 'low';
                    }

                    $rows->push([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku ?? '-',
                        'categories' => $categories ?: '-',
                        'base_price' => $product->base_price,
                        'base_sale_price' => $product->base_sale_price,
                        'color' => $variant->color,
                        'color_hex' => $variant->color_hex ?? '',
                        'size' => $variant->size,
                        'variant_sku' => $variant->sku ?? '-',
                        'variant_price' => $variant->price,
                        'variant_sale_price' => $variant->sale_price,
                        'stock' => $variant->stock,
                        'is_active' => $variant->is_active,
                        'is_featured' => $product->is_featured,
                        'created_at' => $product->created_at->format('d/m/Y'),
                        'stock_level' => $stockLevel,
                    ]);
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên sản phẩm',
            'SKU SP',
            'Danh mục',
            'Giá gốc (VNĐ)',
            'Giá KM gốc (VNĐ)',
            'Màu sắc',
            'Mã màu',
            'Size',
            'SKU biến thể',
            'Giá biến thể (VNĐ)',
            'Giá KM biến thể (VNĐ)',
            'Tồn kho',
            'Trạng thái',
            'Nổi bật',
            'Ngày tạo',
        ];
    }

    public function map($row): array
    {
        $basePrice = $row['base_price'] !== '-' && $row['base_price'] !== null 
            ? (float) $row['base_price'] 
            : null;
        $baseSalePrice = $row['base_sale_price'] !== '-' && $row['base_sale_price'] !== null 
            ? (float) $row['base_sale_price'] 
            : null;
        $variantPrice = $row['variant_price'] !== '-' && $row['variant_price'] !== null && $row['variant_price'] !== '' 
            ? (float) $row['variant_price'] 
            : null;
        $variantSalePrice = $row['variant_sale_price'] !== '-' && $row['variant_sale_price'] !== null && $row['variant_sale_price'] !== '' 
            ? (float) $row['variant_sale_price'] 
            : null;

        return [
            $row['product_id'],
            $row['product_name'],
            $row['product_sku'],
            $row['categories'],
            $basePrice,
            $baseSalePrice,
            $row['color'],
            $row['color_hex'],
            $row['size'],
            $row['variant_sku'],
            $variantPrice,
            $variantSalePrice,
            $row['stock'],
            $row['is_active'] ? 'Còn hàng' : 'Hết hàng',
            $row['is_featured'] ? 'Có' : 'Không',
            $row['created_at'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,    // ID
            'B' => 40,   // Tên sản phẩm
            'C' => 12,   // SKU SP
            'D' => 20,   // Danh mục
            'E' => 20,   // Giá gốc
            'F' => 20,   // Giá KM gốc
            'G' => 12,   // Màu sắc
            'H' => 10,   // Mã màu
            'I' => 8,    // Size
            'J' => 14,   // SKU biến thể
            'K' => 20,   // Giá biến thể
            'L' => 25,   // Giá KM biến thể
            'M' => 10,   // Tồn kho
            'N' => 12,   // Trạng thái
            'O' => 10,   // Nổi bật
            'P' => 12,   // Ngày tạo
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'P';
        $rowCount = $sheet->getHighestRow();
        $headerRow = 1;

        // === HEADER STYLES ===
        $sheet->getStyle("A1:{$lastColumn}{$headerRow}")
            ->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 11,
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E40AF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '3B82F6'],
                    ],
                ],
            ]);

        // Set header row height
        $sheet->getRowDimension($headerRow)->setRowHeight(25);

        // === DATA STYLES ===
        $dataStartRow = 2;

        // Style for all data rows
        $sheet->getStyle("A{$dataStartRow}:{$lastColumn}{$rowCount}")
            ->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 10,
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

        // === COLUMN-SPECIFIC ALIGNMENT ===
        // ID - center
        $sheet->getStyle("A{$dataStartRow}:A{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // Product name - left, bold
        $sheet->getStyle("B{$dataStartRow}:B{$rowCount}")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
            ]);

        // SKU columns - center
        $sheet->getStyle("C{$dataStartRow}:C{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        $sheet->getStyle("J{$dataStartRow}:J{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // Categories - left
        $sheet->getStyle("D{$dataStartRow}:D{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ]);

        // Price columns - right with number format
        $priceColumns = ['E', 'F', 'K', 'L'];
        foreach ($priceColumns as $col) {
            $sheet->getStyle("{$col}{$dataStartRow}:{$col}{$rowCount}")
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                    'numberFormat' => [
                        'formatCode' => '#,##0',
                    ],
                ]);
        }

        // Color columns - center
        $sheet->getStyle("G{$dataStartRow}:H{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // Size - center, bold
        $sheet->getStyle("I{$dataStartRow}:I{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
                    'bold' => true,
                ],
            ]);

        // Stock - center
        $sheet->getStyle("M{$dataStartRow}:M{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // Status - center
        $sheet->getStyle("N{$dataStartRow}:N{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // Featured - center
        $sheet->getStyle("O{$dataStartRow}:O{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // Date - center
        $sheet->getStyle("P{$dataStartRow}:P{$rowCount}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        // === ZEBRA STRIPING ===
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            if (($row - $dataStartRow) % 2 == 1) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F9FAFB'],
                        ],
                    ]);
            }
        }

        // === LOW STOCK HIGHLIGHTING ===
        $stockColumn = 'M';
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            $stockValue = $sheet->getCell("{$stockColumn}{$row}")->getValue();
            
            if ($stockValue === 0) {
                // Out of stock - red background
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FEE2E2'],
                        ],
                        'font' => [
                            'color' => ['rgb' => '991B1B'],
                        ],
                    ]);
            } elseif ($stockValue !== null && $stockValue <= 5) {
                // Low stock - orange background
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FEF3C7'],
                        ],
                        'font' => [
                            'color' => ['rgb' => '92400E'],
                        ],
                    ]);
            }
        }

        // === ACTIVE/INACTIVE STATUS HIGHLIGHTING ===
        $statusColumn = 'N';
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            $statusValue = $sheet->getCell("{$statusColumn}{$row}")->getValue();
            
            if ($statusValue === 'Còn hàng') {
                $sheet->getStyle("{$statusColumn}{$row}")
                    ->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => '059669'],
                            'bold' => true,
                        ],
                    ]);
            } elseif ($statusValue === 'Hết hàng') {
                $sheet->getStyle("{$statusColumn}{$row}")
                    ->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => 'DC2626'],
                        ],
                    ]);
            }
        }

        // === FEATURED HIGHLIGHTING ===
        $featuredColumn = 'O';
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            $featuredValue = $sheet->getCell("{$featuredColumn}{$row}")->getValue();
            
            if ($featuredValue === 'Có') {
                $sheet->getStyle("{$featuredColumn}{$row}")
                    ->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => 'D97706'],
                            'bold' => true,
                        ],
                    ]);
            }
        }

        // === FREEZE PANES ===
        $sheet->freezePane("A2");

        // === PROTECT HEADER ROW ===
        $sheet->getStyle("A1:{$lastColumn}1")->getProtection()->setLocked(true);

        return [];
    }
}
