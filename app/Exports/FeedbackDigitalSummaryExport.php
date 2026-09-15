<?php

namespace App\Exports;

use App\Support\FeedbackResults\DigitalReport;
use App\Support\LocalTime;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * ملخص المنصات الرقمية — ورقة لكل قسم، تجميعية بالكامل (بلا بيانات مواطن).
 *
 * ⚠️ الأرقام كلها من DigitalReport نفسه الذي تعرضه الشاشة — لا يُعاد حساب نسبة هنا.
 * ورقة «التوزيعات» **طويلة لا عريضة** (سؤال · إجابة · عدد · مقام · نسبة): الشكل الصالح
 * لجدول محوري، ومقام كل صف فيها ظاهر فلا تُقرأ نسبة على غير أساسها.
 * والنصوص الحرة **كاملة** لا الأحدث: الشاشة تختصر لضيق المساحة، والملف لا يحتمل الاختصار.
 */
class FeedbackDigitalSummaryExport implements WithMultipleSheets
{
    public function __construct(
        protected DigitalReport $report,
        protected string $officeOrder = 'desc',
    ) {}

    public function sheets(): array
    {
        $headline = $this->report->headline();
        $score    = $this->report->scale('q205');

        return [
            new FeedbackDashboardSheet(
                __('home.fr_export_sheet_summary'),
                [__('home.fr_export_item'), __('home.fr_export_value')],
                array_merge($this->report->filters()->describe(), [
                    [__('home.fr_export_generated_at'), LocalTime::stamp(now())],
                    [__('home.fr_dg_kpi_total'), $headline['total']],
                    [__('home.fr_dg_kpi_booked'), $this->share($headline['booked_percent'], $headline['booked'], $headline['total'])],
                    [__('home.fr_dg_kpi_score'), ($headline['score_avg'] ?? '—').' — '.__('home.fr_dg_base', ['base' => $headline['score_base']])],
                    [__('home.fr_dg_kpi_on_time'), $this->share($headline['on_time_percent'], $headline['on_time'], $headline['on_time_base'])],
                ]),
                summary: true,
            ),

            new FeedbackDashboardSheet(
                __('home.fr_export_sheet_distributions'),
                [__('home.fr_export_question'), __('home.fr_export_answer'), __('home.fr_export_opinions_count'), __('home.fr_export_base'), __('home.fr_export_percent')],
                collect($this->report->sections())->flatten(1)
                    ->flatMap(fn ($dist) => collect($dist['rows'])->map(fn ($row) => [
                        ltrim($dist['key'], 'q').' — '.$dist['title'].($dist['multi'] ? ' *' : ''),
                        $row['label'],
                        $row['count'],
                        $dist['base'],
                        $row['percent'],
                    ]))
                    ->values()->all(),
            ),

            new FeedbackDashboardSheet(
                $score['title'],
                [__('home.fr_export_score'), __('home.fr_export_opinions_count'), __('home.fr_export_percent')],
                array_merge(
                    collect($score['distribution'])->map(fn ($d) => [$d['score'], $d['count'], $d['percent']])->all(),
                    [['', '', '']],
                    collect($score['platforms'])->map(fn ($p) => [$p['label'], $p['count'], $p['avg']])->all(),
                ),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_dg_trend'),
                [__('home.fr_export_month'), __('home.fr_export_opinions_count'), __('home.fr_dg_booked_count'), __('home.fr_dg_kpi_booked'), __('home.fr_dg_kpi_score')],
                collect($this->report->monthlyTrend())
                    ->map(fn ($m) => [$m['label'], $m['count'], $m['booked'], $m['booked_percent'], $m['score_avg']])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_dg_offices'),
                [
                    __('home.fr_office'), __('home.fr_governorate'), __('home.fr_export_opinions_count'),
                    __('home.fr_dg_booked_count'), __('home.fr_dg_kpi_booked'), __('home.fr_dg_kpi_score'),
                    __('home.fr_export_sample_col', ['min' => $this->report->minSample()]),
                ],
                $this->report->officesTable($this->officeOrder)->map(fn ($o) => [
                    $o['office'], $o['governorate'], $o['count'], $o['booked'], $o['booked_percent'], $o['score_avg'],
                    $o['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short'),
                ])->all(),
            ),

            $this->textsSheet('q204'),
            $this->textsSheet('q210'),
        ];
    }

    private function textsSheet(string $key): FeedbackDashboardSheet
    {
        $block = $this->report->texts($key, limit: null);

        return new FeedbackDashboardSheet(
            $block['title'],
            [__('home.fr_date'), __('home.fr_office'), __('home.fr_export_text')],
            $block['items']->map(fn ($i) => [LocalTime::date($i['date']), $i['office'], $i['text']])->all(),
        );
    }

    /** «٣٥٪ (٧ من ٢٠)» — النسبة مع عدّها ومقامها. */
    private function share(?float $percent, int $count, int $base): string
    {
        return $percent === null ? '—' : $percent.'% ('.__('home.fr_dg_count_of_base', ['count' => $count, 'base' => $base]).')';
    }
}
