<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class InventoriesExport implements FromCollection, WithMapping, WithHeadings, WithEvents
{
    use Exportable;

    private $rowNumber = 0;

    public function collection()
    {
        return Inventory::all();
    }

    public function headings(): array
    {
        return ["No", "Nama Barang", "Merek", "Kuantitas", "Kondisi", "Tgl Pengadaan"];
    }

    public function map($inventory): array
    {
        $condition = '';
        switch ($inventory->condition) {
            case 'good':
                $condition = 'Layak pakai';
                break;
            case 'damaged':
                $condition = 'Rusak ringan';
                break;
            default:
                $condition = 'Rusak';
        }

        return [
            ++$this->rowNumber,
            $inventory->name,
            $inventory->brand,
            $inventory->qty,
            $condition,
            $inventory->procurement_date,
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getColumnDimension('A')->setAutoSize(true);
                $event->sheet->getColumnDimension('B')->setAutoSize(true);
                $event->sheet->getColumnDimension('C')->setAutoSize(true);
                $event->sheet->getColumnDimension('D')->setAutoSize(true);
                $event->sheet->getColumnDimension('E')->setAutoSize(true);
                $event->sheet->getColumnDimension('F')->setAutoSize(true);

                $event->sheet->insertNewRowBefore(1, 2);
                $event->sheet->mergeCells('A1:F1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->setCellValue('A1', 'Data Inventaris Laundry');
                $event->sheet->setCellValue('A2', 'Tgl : ' . date('d/m/Y'));
                $event->sheet->getStyle('A1')->getFont()->setBold(true);
                $event->sheet->getStyle('A3:F3')->getFont()->setBold(true);
                $event->sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $event->sheet->getStyle('A3:F' . $event->sheet->getHighestRow())->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
            }
        ];
    }
}
