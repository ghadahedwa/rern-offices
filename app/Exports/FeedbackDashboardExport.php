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

            // فارغة لمن لا يملك feedback.rejected — الحارس في DashboardReport::ipClusters
            new FeedbackDashboardSheet(
                __('home.fr_export_sheet_clusters'),
                [
                    __('home.fr_type'), __('home.fr_office'), __('home.fr_ip_line'),
                    __('home.fr_export_opinions_count'), __('home.fr_devices'),
                    __('home.fr_export_first_at'), __('home.fr_export_last_at'),
                ],
                $this->report->ipClusters()->map(fn ($c) => [
                    __('home.fr_type_'.$c['type']), $c['office'], $c['ip'], $c['total'], $c['devices'],
                    \App\Support\LocalTime::stamp($c['first_at']), \App\Support\LocalTime::stamp($c['last_at']),
                ])->all(),
            ),
        ];
    }

    /** «35% (7 من 20)» — النسبة مع عدّها، فالنسبة وحدها تُخفي صِغر العينة. */
    private function shareText(array $share): string
    {
        return $share['percent'] === null
            ? '—'
            : $share['percent'].'% ('.__('home.fr_anonymous_of_total', ['anonymous' => $share['anonymous'], 'total' => $share['total']]).')';
    }

    /** ورقة الملخص: الفلتر المطبَّق أولاً — رقم بلا سياق فلتره بلا معنى. */
    private function summaryRows(): array
    {
        $kpis     = $this->report->kpis();
        $identity = $this->report->identityShare();
        $digital  = $this->report->digitalHeadline();

        return array_merge(
            $this->report->filters()->describe(),
            [
                [__('home.fr_export_generated_at'), \App\Support\LocalTime::stamp(now())],
                [__('home.fr_total_ratings'), $kpis['ratings']],
                [__('home.fr_total_suggestions'), $kpis['suggestions']],
                [__('home.fr_avg_overall'), $kpis['avg_overall'] ?? '—'],
                [__('home.fr_rated_offices'), $kpis['rated_offices']],
                [__('home.fr_export_anonymous_ratings'), $this->shareText($identity['ratings'])],
                [__('home.fr_export_anonymous_suggestions'), $this->shareText($identity['suggestions'])],
                [__('home.fr_export_anonymous_digital'), $this->shareText($identity['digital'])],
                [__('home.fr_digital').' — '.__('home.fr_dg_kpi_total'), $digital['total']],
                [__('home.fr_digital').' — '.__('home.fr_dg_kpi_booked'), $digital['booked_percent'] !== null ? $digital['booked_percent'].'% ('.__('home.fr_dg_count_of_base', ['count' => $digital['booked'], 'base' => $digital['total']]).')' : '—'],
                [__('home.fr_digital').' — '.__('home.fr_dg_kpi_score'), ($digital['score_avg'] ?? '—').' — '.__('home.fr_dg_base', ['base' => $digital['score_base']])],
                [__('home.fr_export_sample'), __('home.fr_export_sample_note', ['min' => $this->report->minSample()])],
            ],
        );
    }
}
