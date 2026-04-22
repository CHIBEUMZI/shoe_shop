<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    protected array $filters = [];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Order::query()
            ->with(['items.variant'])
            ->when(!empty($this->filters['search']), function ($q) {
                $kw = trim($this->filters['search']);
                $q->where(function ($sub) use ($kw) {
                    $sub->where('code', 'like', "%{$kw}%")
                        ->orWhere('customer_name', 'like', "%{$kw}%")
                        ->orWhere('customer_phone', 'like', "%{$kw}%")
                        ->orWhere('customer_email', 'like', "%{$kw}%");
                });
            })
            ->when(!empty($this->filters['status']), function ($q) {
                $q->where('status', $this->filters['status']);
            })
            ->when(!empty($this->filters['payment_status']), function ($q) {
                $q->where('payment_status', $this->filters['payment_status']);
            })
            ->orderBy('created_at', 'desc');

        $orders = $query->get();

        $rows = collect();
        foreach ($orders as $order) {
            $address = implode(', ', array_filter([
                $order->address_line,
                $order->ward,
                $order->district,
                $order->province,
            ]));

            $isFirstItem = true;
            foreach ($order->items as $item) {
                $variantInfo = [];
                if ($item->color) $variantInfo[] = $item->color;
                if ($item->size) $variantInfo[] = $item->size;
                $variantLabel = implode(' / ', $variantInfo) ?: '-';

                $rows->push([
                    'order_code' => $order->code,
                    'customer_name' => $isFirstItem ? $order->customer_name : '',
                    'customer_phone' => $isFirstItem ? ($order->customer_phone ?? '-') : '',
                    'address' => $isFirstItem ? ($address ?: '-') : '',
                    'payment_method' => $isFirstItem ? $this->paymentMethodText($order->payment_method) : '',
                    'payment_status' => $isFirstItem ? $this->paymentStatusText($order->payment_status) : '',
                    'order_status' => $isFirstItem ? $this->orderStatusText($order->status) : '',
                    'product_name' => $item->product_name,
                    'variant' => $variantLabel,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'grand_total' => $isFirstItem ? (float) $order->grand_total : '',
                    'created_at' => $isFirstItem ? $order->created_at->format('d/m/Y H:i') : '',
                    'is_first_item' => $isFirstItem,
                ]);
                $isFirstItem = false;
            }

            if ($order->items->isEmpty()) {
                $rows->push([
                    'order_code' => $order->code,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone ?? '-',
                    'address' => $address ?: '-',
                    'payment_method' => $this->paymentMethodText($order->payment_method),
                    'payment_status' => $this->paymentStatusText($order->payment_status),
                    'order_status' => $this->orderStatusText($order->status),
                    'product_name' => '-',
                    'variant' => '-',
                    'quantity' => '',
                    'unit_price' => '',
                    'line_total' => '',
                    'grand_total' => (float) $order->grand_total,
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                    'is_first_item' => true,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Mã đơn hàng',
            'Khách hàng',
            'Điện thoại',
            'Địa chỉ',
            'PTTT',
            'Thanh toán',
            'Trạng thái',
            'Tên sản phẩm',
            'Phân loại (Màu / Size)',
            'SL',
            'Đơn giá (VNĐ)',
            'Thành tiền (VNĐ)',
            'Tổng cộng (VNĐ)',
            'Ngày tạo',
        ];
    }

    public function map($row): array
    {
        return [
            $row['order_code'],
            $row['customer_name'],
            $row['customer_phone'],
            $row['address'],
            $row['payment_method'],
            $row['payment_status'],
            $row['order_status'],
            $row['product_name'],
            $row['variant'],
            $row['quantity'],
            $row['unit_price'] !== '' ? $row['unit_price'] : null,
            $row['line_total'] !== '' ? $row['line_total'] : null,
            $row['grand_total'] !== '' ? $row['grand_total'] : null,
            $row['created_at'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,   // Mã đơn
            'B' => 28,   // Khách hàng
            'C' => 14,   // Điện thoại
            'D' => 38,   // Địa chỉ
            'E' => 10,   // PTTT
            'F' => 16,   // Thanh toán
            'G' => 16,   // Trạng thái
            'H' => 38,   // Tên sản phẩm
            'I' => 18,   // Phân loại
            'J' => 6,    // SL
            'K' => 16,   // Đơn giá
            'L' => 16,   // Thành tiền
            'M' => 18,   // Tổng cộng
            'N' => 16,   // Ngày tạo
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'N';
        $rowCount = $sheet->getHighestRow();
        $headerRow = 1;

        $sheet->getStyle("A1:{$lastColumn}{$headerRow}")
            ->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 10,
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
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '3B82F6'],
                    ],
                ],
            ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        $dataStartRow = 2;

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

        // Mã đơn - center, bold
        $sheet->getStyle("A{$dataStartRow}:A{$rowCount}")
            ->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
            ]);

        // Khách hàng - left, bold
        $sheet->getStyle("B{$dataStartRow}:B{$rowCount}")
            ->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                'font' => ['bold' => true],
            ]);

        // Điện thoại, Địa chỉ - left; PTTT, SL - center
        $sheet->getStyle("C{$dataStartRow}:C{$rowCount}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle("D{$dataStartRow}:D{$rowCount}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);
        $sheet->getStyle("E{$dataStartRow}:E{$rowCount}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle("F{$dataStartRow}:G{$rowCount}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle("H{$dataStartRow}:H{$rowCount}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);
        $sheet->getStyle("I{$dataStartRow}:I{$rowCount}")
            ->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true],
            ]);
        $sheet->getStyle("J{$dataStartRow}:J{$rowCount}")
            ->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true],
            ]);

        // Tiền - right
        $moneyColumns = ['K', 'L', 'M'];
        foreach ($moneyColumns as $col) {
            $sheet->getStyle("{$col}{$dataStartRow}:{$col}{$rowCount}")
                ->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'numberFormat' => ['formatCode' => '#,##0'],
                ]);
        }

        // Ngày - center
        $sheet->getStyle("N{$dataStartRow}:N{$rowCount}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // === Dòng đầu tiên của mỗi đơn = nền xanh nhạt ===
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            $isFirst = $sheet->getCell("A{$row}")->getValue() !== '';
            if ($isFirst) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'EFF6FF'],
                        ],
                        'font' => ['bold' => true],
                    ]);
            }
        }

        // === Tô màu trạng thái đơn hàng ===
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            $statusValue = $sheet->getCell("G{$row}")->getValue();
            $this->applyStatusStyle($sheet, $statusValue, "G{$row}");
        }

        // === Tô màu trạng thái thanh toán ===
        for ($row = $dataStartRow; $row <= $rowCount; $row++) {
            $paymentValue = $sheet->getCell("F{$row}")->getValue();
            $this->applyPaymentStyle($sheet, $paymentValue, "F{$row}");
        }

        // === Tổng cộng - đỏ, bold ===
        $sheet->getStyle("M{$dataStartRow}:M{$rowCount}")
            ->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
            ]);

        // Freeze panes
        $sheet->freezePane("A2");

        // Protect header
        $sheet->getStyle("A1:{$lastColumn}1")->getProtection()->setLocked(true);

        return [];
    }

    protected function applyStatusStyle(Worksheet $sheet, ?string $status, string $cell)
    {
        $colorMap = [
            'Chờ xử lý' => ['rgb' => 'F59E0B', 'bold' => true],
            'Đã xác nhận' => ['rgb' => '3B82F6', 'bold' => true],
            'Đang chuẩn bị' => ['rgb' => '6366F1', 'bold' => true],
            'Đang giao' => ['rgb' => '0EA5E9', 'bold' => true],
            'Hoàn thành' => ['rgb' => '059669', 'bold' => true],
            'Đã hủy' => ['rgb' => 'DC2626', 'bold' => false],
        ];

        if (isset($colorMap[$status])) {
            $sheet->getStyle($cell)
                ->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => $colorMap[$status]['rgb']],
                        'bold' => $colorMap[$status]['bold'],
                    ],
                ]);
        }
    }

    protected function applyPaymentStyle(Worksheet $sheet, ?string $status, string $cell)
    {
        $colorMap = [
            'Chưa thanh toán' => ['rgb' => '6B7280', 'bold' => false],
            'Chờ thanh toán' => ['rgb' => 'F59E0B', 'bold' => true],
            'Đã thanh toán' => ['rgb' => '059669', 'bold' => true],
            'Thất bại' => ['rgb' => 'DC2626', 'bold' => true],
            'Hoàn tiền' => ['rgb' => '8B5CF6', 'bold' => true],
        ];

        if (isset($colorMap[$status])) {
            $sheet->getStyle($cell)
                ->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => $colorMap[$status]['rgb']],
                        'bold' => $colorMap[$status]['bold'],
                    ],
                ]);
        }
    }

    protected function paymentMethodText(?string $method): string
    {
        return match ($method) {
            'cod' => 'COD',
            'vnpay' => 'VNPay',
            'momo' => 'MoMo',
            default => $method ?? '-',
        };
    }

    protected function paymentStatusText(?string $status): string
    {
        return match ($status) {
            'unpaid' => 'Chưa thanh toán',
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thất bại',
            'refunded' => 'Hoàn tiền',
            default => $status ?? '-',
        };
    }

    protected function orderStatusText(?string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => $status ?? '-',
        };
    }
}
