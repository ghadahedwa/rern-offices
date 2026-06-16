<?php

namespace App\Livewire\Reports;

use App\Exports\StatsComparisonExport;
use App\Models\Governorate;
use App\Models\OfficeStat;
use App\Models\StatType;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('مقارنة الإحصائيات بين المحافظات')]
class StatsComparison extends Component
{
    /** مجموعات الإحصائيات المتاحة: group_key => مفتاح اللغة (نفس مجموعات لوحة التحكم) */
    public const GROUPS = [
        'transactions'        => 'home.stat_group_transactions',
        'shaher_requests'     => 'home.stat_group_shaher',
        'law9_registrations'  => 'home.stat_group_law9',
        'law27_registrations' => 'home.stat_group_law27',
        'registry_requests'   => 'home.stat_group_registry',
        'forms_folders'       => 'home.stat_group_forms_folders',
    ];

    /** مجموعات تُعرض مقسّمة لأعمدة فرعية حسب نوع الإحصائية (نماذج/حوافظ) تحت كل سنة */
    public const BREAKDOWN_GROUPS = ['forms_folders'];

    // ── الفلاتر ──
    public array $groupKeys = [];   // مجموعات مختارة (فارغ = الكل)
    public ?int $year1 = null;
    public ?int $year2 = null;
    public array $governorateIds = [];

    /** snapshot وقت الضغط على "بحث" — العرض يقرأ منه فقط */
    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['groupKeys', 'governorateIds'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );

        $this->defaultYears();
    }

    /** أحدث سنتين بهما بيانات كافتراضي للمقارنة */
    protected function defaultYears(): void
    {
        $years       = $this->availableYears();
        $this->year2 = $years[0] ?? (int) date('Y');
        $this->year1 = $years[1] ?? ($this->year2 - 1);
    }

    /** السنوات المتاحة في البيانات (تنازلي) */
    public function availableYears(): array
    {
        return OfficeStat::query()
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->all();
    }

    public function search(): void
    {
        // السنة الثانية يجب أن تكون أكبر من الأولى
        if ((int) $this->year2 <= (int) $this->year1) {
            Flux::toast(variant: 'warning', text: __('home.report_stats_year_order'));
            return;
        }

        $this->applied = [
            'groupKeys'      => $this->groupKeys,
            'year1'          => (int) $this->year1,
            'year2'          => (int) $this->year2,
            'governorateIds' => $this->governorateIds,
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->defaultYears();
        $this->applied     = [];
        $this->hasSearched = false;
        $this->dispatch('filters-reset');
    }

    /** المحافظات المسموح بها (null = super-admin يرى الكل) */
    protected function allowedGovIds(): ?array
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? null
            : $user->governorates()->pluck('governorates.id')->all();
    }

    /**
     * يبني المصفوفة: صفوف = محافظات · لكل عمود إحصائي مجموع السنة الأولى + الثانية.
     *
     * مجموعات BREAKDOWN_GROUPS تُوسَّع لعدّة أعمدة مستقلة (نماذج/حوافظ)، كلٌّ يتصرّف
     * كمجموعة عادية بمفتاح مركّب "group_key::stat_type_id".
     *
     * @return array{governorates:\Illuminate\Support\Collection, groups:array, data:array, y1:int, y2:int}
     */
    public function buildMatrix(?array $allowedGovIds): array
    {
        $f        = $this->applied;
        $govIds   = $f['governorateIds'] ?? [];
        $selected = $f['groupKeys'] ?? [];
        $y1       = (int) $f['year1'];
        $y2       = (int) $f['year2'];

        // المجموعات الفعلية المعروضة (المختارة أو الكل) — بترتيب التعريف
        $groupKeys = $selected
            ? array_values(array_filter(array_keys(self::GROUPS), fn ($k) => in_array($k, $selected, true)))
            : array_keys(self::GROUPS);

        // أنواع المجموعات المقسّمة المعروضة: group_key => [stat_type_id => name]
        $breakdownKeys = array_values(array_intersect(self::BREAKDOWN_GROUPS, $groupKeys));
        $subTypes      = [];
        if ($breakdownKeys) {
            $subTypes = StatType::query()
                ->whereIn('group_key', $breakdownKeys)
                ->orderBy('order')->orderBy('id')
                ->get(['id', 'name', 'group_key'])
                ->groupBy('group_key')
                ->map(fn ($rows) => $rows->pluck('name', 'id')->all())
                ->all();
        }

        // أعمدة العرض: عادية = group_key · مقسّمة = "group_key::stat_type_id" لكل نوع
        $groups = [];                  // display_key => التسمية
        $colKeyFor = function ($gk, $stid) use ($breakdownKeys) {
            return in_array($gk, $breakdownKeys, true) ? "{$gk}::{$stid}" : $gk;
        };
        foreach ($groupKeys as $k) {
            if (in_array($k, $breakdownKeys, true)) {
                foreach ($subTypes[$k] as $stid => $name) {
                    $groups["{$k}::{$stid}"] = $name;
                }
            } else {
                $groups[$k] = __(self::GROUPS[$k]);
            }
        }

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('id', $govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        $years = array_values(array_unique([$y1, $y2]));

        // مجموع القيم لكل (محافظة، مجموعة، نوع، سنة) — كل الشهور
        $rows = DB::table('office_statistics')
            ->join('stat_types', 'office_statistics.stat_type_id', '=', 'stat_types.id')
            ->join('offices', 'office_statistics.office_id', '=', 'offices.id')
            ->whereIn('stat_types.group_key', $groupKeys)
            ->whereIn('office_statistics.year', $years)
            ->when($allowedGovIds, fn ($q) => $q->whereIn('offices.governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('offices.governorate_id', $govIds))
            ->selectRaw('offices.governorate_id as gid, stat_types.group_key as gk, stat_types.id as stid, office_statistics.year as yr, COALESCE(SUM(office_statistics.value),0) as total')
            ->groupBy('offices.governorate_id', 'stat_types.group_key', 'stat_types.id', 'office_statistics.year')
            ->get();

        $data = []; // [gov_id][display_key][year] = مجموع
        foreach ($rows as $r) {
            $yr = (int) $r->yr;
            $dk = $colKeyFor($r->gk, $r->stid);
            $data[$r->gid][$dk][$yr] = ($data[$r->gid][$dk][$yr] ?? 0) + (int) $r->total;
        }

        return compact('governorates', 'groups', 'data', 'y1', 'y2');
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $m = $this->buildMatrix($this->allowedGovIds());

        return Excel::download(
            new StatsComparisonExport($m['governorates'], $m['groups'], $m['data'], $m['y1'], $m['y2']),
            'stats-comparison-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id')->all();

        $matrix = $this->hasSearched
            ? $this->buildMatrix($allowedGovIds)
            : ['governorates' => collect(), 'groups' => [], 'data' => [], 'y1' => (int) $this->year1, 'y2' => (int) $this->year2];

        return view('livewire.reports.stats-comparison', [
            'matrix'       => $matrix,
            'governorates' => $governorates,
            'groupOptions' => collect(self::GROUPS)->map(fn ($langKey) => __($langKey))->all(),
            'years'        => $this->availableYears(),
        ]);
    }
}
