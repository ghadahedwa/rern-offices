<?php

namespace App\Exports;

use App\Support\Finance\FinanceReport;
use App\Support\Finance\Money;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ورقة الجدول — كالشاشة: صفّ العنوان والفترة، ثم الرأس (اتصالات بثلاثة أعمدة: إيراد · منصرف · رصيد)،
 * ثم الصفوف والإجمالي، ثم العناصر الثمانية لأبنية المحاكم.
 * ⚠️ أرقام خالصة (`WithStrictNullComparison`): «لم يُسجَّل» خانةٌ فارغة، والصفر المسجَّل صفر.
 */
class FinanceReportTableSheet implements FromArray, WithEvents, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    /** صفّ الرأس في الورقة — بعد صفّي العنوان والفترة وصفٍّ فارغ */
    protected const HEAD_ROW = 4;

    protected int $width = 0;
    protected int $lastTableRow = 0;

    public function __construct(protected FinanceReport $report) {}

    public function title(): string
    {
        return __('home.fin_rep_sheet_table');
    }

    public function array(): array
    {
        $r        = $this->report;
        $isUnit   = $r->kind === 'unit';
        $data     = $isUnit ? $r->unitReport() : $r->totalReport();
        $accounts = $r->accounts();

        $head = [$isUnit ? __('home.fin_month') : __('home.fin_unit')];
        foreach ($accounts as $account) {
            if ($account->isLedger()) {
                $label  = __('home.fin_tab_'.$account->key);
                $head[] = $label.' — '.__('home.fin_income');
                $head[] = $label.' — '.__('home.fin_expense');
                $head[] = $label.' — '.($isUnit ? __('home.fin_rep_balance') : __('home.fin_rep_balance_end'));
            } else {
                $head[] = __('home.fin_tab_'.$account->key);
            }
        }
        $head[] = $isUnit ? __('home.fin_rep_orders') : __('home.fin_rep_entered');
        $this->width = count($head);

        $line = function (string $first, array $cells, ?array $telecom, $last) use ($accounts) {
            $out = [$first];
            foreach ($accounts as $account) {
                if ($account->isLedger()) {
                    array_push($out, Money::pounds($telecom['income'] ?? null), Money::pounds($telecom['expense'] ?? null), Money::pounds($telecom['balance'] ?? null));
                } else {
                    $out[] = Money::pounds($cells[$account->key] ?? null);
                }
            }
            $out[] = $last;

            return $out;
        };

        $rows = [[$r->title()], [$r->periodLabel()], [], $head];
        foreach ($data['rows'] as $row) {
            $rows[] = $isUnit
                ? $line($row['label'], $row['cells'], $row['telecom'], $row['orders'])
                : $line($row['unit']->name, $row['cells'], $row['telecom'], __('home.fin_units_of', ['entered' => $row['entered'], 'count' => $row['required']]));
        }
        $rows[] = $line(__('home.fin_total_label'), $data['totals']['cells'], $data['totals']['telecom'], $isUnit ? $data['totals']['orders'] : null);
        $this->lastTableRow = count($rows);

        $items = $r->items();
        if ($items) {
            $rows[] = [];
            $rows[] = [__('home.fin_rep_items_title')];
            foreach ($items as $name => $amount) {
                $rows[] = [$name, Money::pounds($amount)];
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);

                $col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max($this->width, 2));
                $head = self::HEAD_ROW;

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle("A{$head}:{$col}{$head}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C9A847']],
                ]);
                $sheet->getStyle("A{$this->lastTableRow}:{$col}{$this->lastTableRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7F1E0']],
                ]);
                // المبالغ بالجنيه بقرشين، وعمود أوامر الدفع/المُدخَل (الأخير) عددٌ صحيح
                $sheet->getStyle("B".($head + 1).":{$col}".$sheet->getHighestRow())->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("{$col}".($head + 1).":{$col}{$this->lastTableRow}")->getNumberFormat()->setFormatCode('0');
                $sheet->freezePane('B'.($head + 1));
            },
        ];
    }
}
