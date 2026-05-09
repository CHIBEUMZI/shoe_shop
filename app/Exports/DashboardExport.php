<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    public function __construct(protected array $payload = [])
    {
    }

    public function collection(): Collection
    {
        return collect($this->rows());
    }

    public function headings(): array
    {
        return ['Khu vực', 'Chỉ số', 'Giá trị', 'Ghi chú'];
    }

    public function map($row): array
    {
        return [
            $row['section'],
            $row['title'],
            $row['metric'],
            $row['extra'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
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
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        $sheet->freezePane('A2');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setTitle('Dashboard');
            },
        ];
    }

    protected function rows(): array
    {
        $sections = $this->payload['sections'] ?? [];
        $rows = [];

        $overview = $sections['overview'] ?? [];
        $rows[] = [
            'section' => 'Doanh thu',
            'title' => 'Tổng doanh thu',
            'metric' => (float) data_get($overview, 'revenue', 0),
            'extra' => 'Tăng trưởng: ' . (float) data_get($overview, 'revenue_growth', 0) . '%',
        ];
        $rows[] = [
            'section' => 'Đơn hàng',
            'title' => 'Tổng đơn hàng',
            'metric' => (int) data_get($overview, 'orders', 0),
            'extra' => 'Tăng trưởng: ' . (float) data_get($overview, 'orders_growth', 0) . '%',
        ];
        $rows[] = [
            'section' => 'Khách hàng mới',
            'title' => 'Tổng khách hàng',
            'metric' => (int) data_get($overview, 'customers', 0),
            'extra' => 'Tăng trưởng: ' . (float) data_get($overview, 'customers_growth', 0) . '%',
        ];
        $rows[] = [
            'section' => 'Sản phẩm',
            'title' => 'Tổng sản phẩm',
            'metric' => (int) data_get($overview, 'products', 0),
            'extra' => 'Tăng trưởng: ' . (float) data_get($overview, 'products_growth', 0) . '%',
        ];

        $rows[] = ['section' => '---', 'title' => '---', 'metric' => '---', 'extra' => '---'];

        foreach (($sections['chart'] ?? []) as $item) {
            $rows[] = [
                'section' => 'Biểu đồ doanh thu',
                'title' => $item['label'] ?? '',
                'metric' => (float) ($item['value'] ?? 0),
                'extra' => 'Khoảng ngày: ' . ($this->payload['period']['label'] ?? ''),
            ];
        }

        $rows[] = ['section' => '---', 'title' => '---', 'metric' => '---', 'extra' => '---'];

        foreach (($sections['top_products'] ?? []) as $product) {
            $rows[] = [
                'section' => 'Top sản phẩm bán chạy',
                'title' => $product['name'] ?? '',
                'metric' => (int) ($product['sold'] ?? 0),
                'extra' => 'Size/Màu phổ biến đã được tổng hợp',
            ];
        }

        $rows[] = ['section' => '---', 'title' => '---', 'metric' => '---', 'extra' => '---'];

        foreach (($sections['recent_orders'] ?? []) as $order) {
            $rows[] = [
                'section' => 'Đơn hàng gần đây',
                'title' => ($order['code'] ?? '') . ' - ' . ($order['customer_name'] ?? ''),
                'metric' => (float) ($order['total_amount'] ?? 0),
                'extra' => $order['status_label'] ?? ($order['status'] ?? ''),
            ];
        }

        $rows[] = ['section' => '---', 'title' => '---', 'metric' => '---', 'extra' => '---'];

        foreach (($sections['new_customers'] ?? []) as $customer) {
            $rows[] = [
                'section' => 'Khách hàng mới',
                'title' => $customer['name'] ?? '',
                'metric' => 1,
                'extra' => ($customer['email'] ?? '') . ' | ' . ($customer['created_at'] ?? ''),
            ];
        }

        return $rows;
    }
}
