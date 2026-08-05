<?php

namespace App\Exports;

use App\Support\FeedbackResults\DashboardReport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * لوحة نتائج رأي المواطن — ورقة لكل قسم.
 * تجميعية بالكامل: لا اسم مواطن ولا رقم قومي ولا هاتف.
 *
 * ⚠️ الأرقام كلها من DashboardReport نفسه الذي تعرضه الشاشة — لا يُعاد حساب
 * أي متوسط هنا، وإلا صار الملف يخالف اللوحة.
 */
class FeedbackDashboardExport implements WithMultipleSheets
{
    public function __construct(protected DashboardReport $report) {}

    public function sheets(): array
    {
        return [
            new FeedbackDashboardSheet(
                __('home.fr_export_sheet_summary'),
                [__('home.fr_export_item'), __('home.fr_export_value')],
                $this->summaryRows(),
                summary: true,
            ),

            new FeedbackDashboardSheet(
                __('home.fr_criteria_averages'),
                [__('home.fr_export_criterion'), __('home.fr_export_avg_of_five'), __('home.fr_answered_count')],
                collect($this->report->criteriaAverages())->map(fn ($c) => [
                    $c['label'].($c['optional'] ? ' *' : ''),
                    $c['avg'],
                    $c['count'],
                ])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_wait_distribution'),
                [__('home.fr_wait_time'), __('home.fr_export_opinions_count'), __('home.fr_export_percent')],
                collect($this->report->waitDistribution())
                    ->map(fn ($w) => [$w['label'], $w['count'], $w['percent']])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_offices_ranking'),
                [
                    __('home.fr_office'), __('home.fr_governorate'),
                    __('home.fr_export_opinions_count'), __('home.fr_export_avg_of_five'),
                    __('home.fr_export_sample_col', ['min' => $this->report->minSample()]),
                ],
                $this->report->officesTable()->map(fn ($o) => [
                    $o['office'], $o['governorate'], $o['count'], $o['avg'],
                    $o['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short'),
                ])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_governorates_ranking'),
                [
                    __('home.fr_governorate'), __('home.fr_export_opinions_count'),
                    __('home.fr_export_avg_of_five'),
                    __('home.fr_export_sample_col', ['min' => $this->report->minSample()]),
                ],
                $this->report->governoratesRanking()->map(fn ($g) => [
                    $g['governorate'], $g['count'], $g['avg'],
                    $g['enough'] ? __('home.fr_export_sample_enough') : __('home.fr_export_sample_short'),
                ])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_monthly_trend'),
                [__('home.fr_export_month'), __('home.fr_export_opinions_count'), __('home.fr_export_avg_of_five')],
                collect($this->report->monthlyTrend())
                    ->map(fn ($m) => [$m['label'], $m['count'], $m['avg']])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_top_topics'),
                [__('home.fr_export_topic'), __('home.fr_export_domain'), __('home.fr_export_suggestions_count')],
                $this->report->topicsPriority()['topics']
                    ->map(fn ($t) => [$t['name'], $t['domain'], $t['count']])->all(),
            ),

            new FeedbackDashboardSheet(
                __('home.fr_rejected_summary'),
                [__('home.fr_reason'), __('home.fr_export_attempts_count')],
                $this->report->rejectedSummary()
                    ->map(fn ($count, $reason) => [__('home.fr_reason_'.$reason), $count])
                    ->values()->all(),
            ),
        ];
    }

    /** ورقة الملخص: الفلتر المطبَّق أولاً — رقم بلا سياق فلتره بلا معنى. */
    private function summaryRows(): array
    {
        $kpis = $this->report->kpis();

        return array_merge(
            $this->report->filters()->describe(),
            [
                [__('home.fr_export_generated_at'), \App\Support\LocalTime::stamp(now())],
                [__('home.fr_total_ratings'), $kpis['ratings']],
                [__('home.fr_total_suggestions'), $kpis['suggestions']],
                [__('home.fr_avg_overall'), $kpis['avg_overall'] ?? '—'],
                [__('home.fr_rated_offices'), $kpis['rated_offices']],
                [__('home.fr_export_sample'), __('home.fr_export_sample_note', ['min' => $this->report->minSample()])],
            ],
        );
    }
}
