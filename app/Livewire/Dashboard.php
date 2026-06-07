<?php

namespace App\Livewire;

use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\WithoutUrlPagination;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[WithoutUrlPagination]
class Dashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEvent = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user         = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $govIds = $isSuperAdmin
            ? null
            : $user->governorates()->pluck('governorates.id');

        $officesQuery = Office::query();
        if (! $isSuperAdmin) {
            $officesQuery->whereIn('governorate_id', $govIds);
        }

        $totalOffices      = $officesQuery->count();
        $totalGovernorates = $isSuperAdmin
            ? Governorate::count()
            : $user->governorates()->count();
        $totalUsers     = $isSuperAdmin ? User::count() : null;
        $addedThisMonth = (clone $officesQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $needsVisitCount = (clone $officesQuery)
            ->where(fn ($q) => $q
                ->whereNull('visited_at')
                ->orWhere('visited_at', '<', now()->subMonths(6))
            )
            ->count();

        // Bar chart: توزيع المقرات على المحافظات (مرتبة حسب order)
        $officesByGovRaw = (clone $officesQuery)
            ->select('governorate_id', DB::raw('count(*) as total'))
            ->with('governorate:id,name,order')
            ->groupBy('governorate_id')
            ->get()
            ->sortBy('governorate.order');

        // Tooltip: إحصائيات آخر عام لكل محافظة
        $tooltipGroups = [
            'transactions'        => 'التوثيق',
            'law9_registrations'  => 'قانون ٩',
            'law27_registrations' => 'قانون ٢٧',
            'registry_requests'   => 'السجل',
        ];

        $tooltipStatsByGov = []; // governorate_id => [groupKey => [label, years => [[year, total]]]]
        foreach ($tooltipGroups as $groupKey => $label) {
            $years = \App\Models\OfficeStat::whereHas('statType', fn ($q) => $q->where('group_key', $groupKey))
                ->where('value', '>', 0)
                ->distinct()
                ->orderByDesc('year')
                ->limit(2)
                ->pluck('year');

            if ($years->isEmpty()) continue;

            foreach ($years as $year) {
                DB::table('office_statistics')
                    ->join('stat_types', 'office_statistics.stat_type_id', '=', 'stat_types.id')
                    ->join('offices', 'office_statistics.office_id', '=', 'offices.id')
                    ->where('stat_types.group_key', $groupKey)
                    ->where('office_statistics.year', $year)
                    ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('offices.governorate_id', $govIds))
                    ->selectRaw('offices.governorate_id, sum(office_statistics.value) as total')
                    ->groupBy('offices.governorate_id')
                    ->get()
                    ->each(function ($row) use (&$tooltipStatsByGov, $groupKey, $label, $year) {
                        if ((int) $row->total === 0) return;
                        $tooltipStatsByGov[$row->governorate_id][$groupKey]['label'] = $label;
                        $tooltipStatsByGov[$row->governorate_id][$groupKey]['years'][] = [
                            'year'  => $year,
                            'total' => (int) $row->total,
                        ];
                    });
            }
        }

        // بناء tooltip data مرتبة بنفس ترتيب الـ chart
        $govTooltipData = $officesByGovRaw->values()->map(
            fn ($r) => $tooltipStatsByGov[$r->governorate_id] ?? []
        )->values();

        $officesByGov = $officesByGovRaw->map(fn ($r) => [
            'name'  => $r->governorate?->name ?? '—',
            'total' => $r->total,
        ]);

        // Horizontal bar chart: توزيع المقرات حسب الحالة الإنشائية
        $officesByStructure = (clone $officesQuery)
            ->select('structural_condition_id', DB::raw('count(*) as total'))
            ->with('structuralCondition:id,name')
            ->groupBy('structural_condition_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->structuralCondition?->name ?? 'غير محدد',
                'total' => $r->total,
            ]);

        // Donut chart: توزيع المقرات حسب النوع
        $officesByType = (clone $officesQuery)
            ->select('type_id', DB::raw('count(*) as total'))
            ->with('officeType:id,name')
            ->groupBy('type_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->officeType?->name ?? 'غير محدد',
                'total' => $r->total,
            ]);

        // ملخص إحصائيات — 3 مجموعات سنوية
        $statGroups = [
            'transactions'         => 'home.stat_group_transactions',
            'shaher_requests'      => 'home.stat_group_shaher',
            'law9_registrations'   => 'home.stat_group_law9',
            'law27_registrations'  => 'home.stat_group_law27',
            'registry_requests'    => 'home.stat_group_registry',
            'forms_folders'        => 'home.stat_group_forms_folders',
        ];

        $statsSummary = collect($statGroups)->map(function ($langKey, $groupKey) use ($isSuperAdmin, $govIds) {
            $years = \App\Models\OfficeStat::whereHas('statType', fn ($q) => $q->where('group_key', $groupKey))
                ->when(! $isSuperAdmin, fn ($q) => $q->whereHas('office', fn ($o) => $o->whereIn('governorate_id', $govIds)))
                ->where('value', '>', 0)
                ->selectRaw('year, sum(value) as total')
                ->groupBy('year')
                ->orderByDesc('year')
                ->limit(2)
                ->get();

            $latestTotal = $years->first()?->total ?? 0;
            $prevTotal   = $years->skip(1)->first()?->total ?? 0;
            $change      = $prevTotal > 0
                ? round((($latestTotal - $prevTotal) / $prevTotal) * 100, 1)
                : null;

            return [
                'label'       => $langKey,
                'latestYear'  => $years->first()?->year,
                'latestTotal' => $latestTotal,
                'prevTotal'   => $prevTotal,
                'change'      => $change,
            ];
        });

        $onlineUsers = $isSuperAdmin
            ? DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(10)->timestamp)
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->select('users.id', 'users.name', 'sessions.last_activity', 'sessions.ip_address')
                ->get()
            : collect();

        $activitiesQuery = Activity::with('causer')
            ->when($this->search, fn ($q) => $q
                ->where('description', 'like', "%{$this->search}%")
                ->orWhereHasMorph('causer', User::class, fn ($u) => $u->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
            ->latest();

        if (! $isSuperAdmin) {
            $activitiesQuery->where('causer_id', $user->id)
                            ->where('causer_type', User::class);
        }

        $activities = $activitiesQuery->paginate(10);

        return view('livewire.dashboard', compact(
            'totalOffices', 'totalGovernorates', 'totalUsers',
            'addedThisMonth', 'needsVisitCount', 'onlineUsers', 'activities', 'isSuperAdmin',
            'officesByGov', 'officesByType', 'officesByStructure',
            'user', 'statsSummary', 'govTooltipData'
        ));
    }
}
