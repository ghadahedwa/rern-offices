<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * تنسيق موحّد لأوراق ملفات رأي المواطن: اتجاه عربي، رأس ذهبي، حدود، تثبيت الرأس.
 * نفس هوية باقي ملفات المشروع (#c9a847).
 */
trait FormatsFeedbackSheet
{
    protected function styleSheet(Worksheet $sheet, string $lastCol, bool $centerBody = true): void
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->setRightToLeft(true);

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

            if ($centerBody) {
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }
        }

        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    /** ورقة ملخص (تسمية/قيمة) بلا رأس جدول — لأوراق اللوحة. */
    protected function styleSummarySheet(Worksheet $sheet, string $lastCol): void
    {
        $this->styleSheet($sheet, $lastCol, false);

        $lastRow = $sheet->getHighestRow();

        if ($lastRow > 1) {
            $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '555555']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E0']],
            ]);
        }
    }
}
