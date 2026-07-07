<?php

namespace App\Livewire;

use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class Dashboard extends Component
{
    use WithPagination, WithoutUrlPagination;

    public string $search = '';
    public string $filterEvent = '';
    public string $deletePeriod = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function deleteOldActivities(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $cutoff = match ($this->deletePeriod) {
            '3days'  => now()->subDays(3),
            '1week'  => now()->subWeek(),
            '2weeks' => now()->subWeeks(2),
            '3weeks' => now()->subWeeks(3),
            '1month' => now()->subMonth(),
            default  => null,
        };

        if (! $cutoff) {
            return;
        }

        $deleted = Activity::where('created_at', '<', $cutoff)->delete();

        $this->deletePeriod = '';
        $this->resetPage();

        if ($deleted > 0) {
            \Flux\Flux::toast(variant: 'success', text: __('home.activity_deleted_success', ['count' => $deleted]));
        } else {
            \Flux\Flux::toast(variant: 'warning', text: __('home.activity_delete_none'));
        }
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user         = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $canView      = $isSuperAdmin || $user->can('offices.view');
        $canEdit      = $isSuperAdmin || $user->can('offices.edit');
        $canEditVehicles = $isSuperAdmin || $user->can('vehicles.edit');
        $canViewVehicles = $isSuperAdmin || $user->can('vehicles.view');
        // أقسام تجميع بيانات المقرات (جديد هذا الشهر، تحتاج زيارة، رسما النوع/الحالة، ملخص الإحصائيات)
        // تُحجب عمّن لا يملك صلاحية استعراض المقرات — المتصلون الآن وسجل النشاط يظلّان للجميع
        $canViewOfficeStats = $isSuperAdmin || $user->can('offices.index');

        // مستوى المستخدم = أعلى مستوى بين أدواره (مفتش=1 افتراضياً)
        $myLevel      = (int) ($user->roles()->max('level') ?: 1);
        $isSupervisor = ! $isSuperAdmin && $myLevel >= 2;

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

        $addedThisMonth  = 0;
        $needsVisitCount = 0;
        if ($canViewOfficeStats) {
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
        }

        // ملخص السيارات المتنقلة — محجوب عمّن لا يملك vehicles.index، نفس نطاق المحافظات
        $canViewVehicleStats  = $isSuperAdmin || $user->can('vehicles.index');
        $totalVehicles        = 0;
        $vehiclesWorking      = 0;
        $vehiclesMaintenance  = 0;
        $vehiclesStopped      = 0;
        if ($canViewVehicleStats) {
            $vehiclesQuery = Vehicle::query();
            if (! $isSuperAdmin) {
                $vehiclesQuery->whereIn('governorate_id', $govIds);
            }

            $totalVehicles = (clone $vehiclesQuery)->count();

            $vehicleStatusCounts = (clone $vehiclesQuery)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $vehiclesWorking     = (int) ($vehicleStatusCounts['working'] ?? 0);
            $vehiclesMaintenance = (int) ($vehicleStatusCounts['maintenance'] ?? 0);
            $vehiclesStopped     = (int) ($vehicleStatusCounts['stopped'] ?? 0);
        }

        // ملخص إحصائيات التوثيق للسيارات (آخر سنة فقط، مفصولة لكل نوع) — نفس نطاق السيارات الظاهرة للمستخدم
        // نفس تسميات vehicle_stat_* المستخدمة في Vehicles\StatTab\Documentation (بالـ id)
        $vehicleStatTypeLabels = [
            1 => 'vehicle_stat_transactions',
            2 => 'vehicle_stat_form_sales',
            3 => 'vehicle_stat_folder_sales',
        ];
        $vehicleStatsSummary = collect();
        if ($canViewVehicleStats) {
            $vehicleStatsSummary = collect($vehicleStatTypeLabels)->map(function ($langKey, $typeId) use ($isSuperAdmin, $govIds) {
                $row = VehicleStat::where('stat_type_id', $typeId)
                    ->when(! $isSuperAdmin, fn ($q) => $q->whereHas('vehicle', fn ($v) => $v->whereIn('governorate_id', $govIds)))
                    ->where('value', '>', 0)
                    ->selectRaw('year, sum(value) as total')
                    ->groupBy('year')
                    ->orderByDesc('year')
                    ->first();

                return [
                    'label'       => __('home.' . $langKey),
                    'latestYear'  => $row->year ?? null,
                    'latestTotal' => $row->total ?? 0,
                ];
            });
        }

        // Bar chart: توزيع المقرات (والسيارات) على المحافظات (مرتبة حسب order)
        $officesByGovRaw = (clone $officesQuery)
            ->select('governorate_id', DB::raw('count(*) as total'))
            ->with('governorate:id,name,order')
            ->groupBy('governorate_id')
            ->get()
            ->sortBy('governorate.order');

        // نفس محاور المحافظات بتاعة المقرات — بيضاف السيارات كـ dataset تاني بمحاذاتها
        $vehiclesByGovCounts = collect();
        if ($canViewVehicleStats) {
            $vehiclesByGovCounts = Vehicle::query()
                ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->select('governorate_id', DB::raw('count(*) as total'))
                ->groupBy('governorate_id')
                ->pluck('total', 'governorate_id');
        }
        $vehiclesByGov = $officesByGovRaw->map(
            fn ($r) => (int) ($vehiclesByGovCounts[$r->governorate_id] ?? 0)
        )->values();

        // Tooltip: إحصائيات آخر عام لكل محافظة
        $tooltipGroups = [
            'transactions'        => 'التوثيق',
            'law9_registrations'  => 'قانون ٩',
            'law27_registrations' => 'قانون ٢٧',
            'registry_requests'   => 'السجل',
        ];

        // tooltip الإحصائيات على رسم المحافظات — محجوب عمّن لا يملك offices.index (يبقى العدد فقط)
        $tooltipStatsByGov = []; // governorate_id => [groupKey => [label, years => [[year, total]]]]
        foreach ($canViewOfficeStats ? $tooltipGroups : [] as $groupKey => $label) {
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

        // رسما النوع والحالة الإنشائية — محجوبان عمّن لا يملك offices.index
        $officesByStructure = collect();
        $officesByType      = collect();
        if ($canViewOfficeStats) {
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
        }

        // ملخص إحصائيات — 3 مجموعات سنوية
        $statGroups = [
            'transactions'         => 'home.stat_group_transactions',
            'shaher_requests'      => 'home.stat_group_shaher',
            'law9_registrations'   => 'home.stat_group_law9',
            'law27_registrations'  => 'home.stat_group_law27',
            'registry_requests'    => 'home.stat_group_registry',
            'forms_folders'        => 'home.stat_group_forms_folders',
        ];

        // حد أدنى للسنة لمجموعات معينة (بيانات السنوات الأقدم غير مكتملة)
        $statGroupMinYear = [
            'forms_folders' => 2026,
        ];

        // مجموعات تُعرض مفصّلة حسب النوع (بدل مقارنة السنوات)
        $statGroupBreakdown = ['forms_folders'];

        // ملخص الإحصائيات السنوية — محجوب عمّن لا يملك offices.index
        $statsSummary = collect();
        if ($canViewOfficeStats) {
        $statsSummary = collect($statGroups)->map(function ($langKey, $groupKey) use ($isSuperAdmin, $govIds, $statGroupMinYear, $statGroupBreakdown) {
            $minYear = $statGroupMinYear[$groupKey] ?? null;

            // بطاقة مفصّلة: عرض كل نوع على حدة، مع مقارنة آخر سنتين لكل نوع
            if (in_array($groupKey, $statGroupBreakdown)) {
                $rows = \App\Models\OfficeStat::whereHas('statType', fn ($q) => $q->where('group_key', $groupKey))
                    ->when(! $isSuperAdmin, fn ($q) => $q->whereHas('office', fn ($o) => $o->whereIn('governorate_id', $govIds)))
                    ->when($minYear, fn ($q) => $q->where('year', '>=', $minYear))
                    ->where('value', '>', 0)
                    ->with('statType:id,name')
                    ->selectRaw('stat_type_id, year, sum(value) as total')
                    ->groupBy('stat_type_id', 'year')
                    ->get();

                // سنوات البطاقة (موحّدة لكل الأنواع): أحدث سنتين
                $distinctYears = $rows->pluck('year')->unique()->sortDesc()->values();
                $latestYear    = $distinctYears->first();
                $prevYear      = $distinctYears->skip(1)->first();

                $breakdown = $rows->groupBy('stat_type_id')->map(function ($typeRows) use ($latestYear, $prevYear) {
                    $byYear      = $typeRows->keyBy('year');
                    $latestTotal = $byYear[$latestYear]->total ?? 0;
                    $prevTotal   = $prevYear ? ($byYear[$prevYear]->total ?? 0) : 0;
                    $change      = $prevTotal > 0
                        ? round((($latestTotal - $prevTotal) / $prevTotal) * 100, 1)
                        : null;

                    return [
                        'name'        => $typeRows->first()->statType?->name ?? '—',
                        'latestTotal' => $latestTotal,
                        'prevTotal'   => $prevTotal,
                        'change'      => $change,
                    ];
                })->values();

                return [
                    'label'      => $langKey,
                    'latestYear' => $latestYear,
                    'prevYear'   => $prevYear,
                    'breakdown'  => $breakdown,
                ];
            }

            $years = \App\Models\OfficeStat::whereHas('statType', fn ($q) => $q->where('group_key', $groupKey))
                ->when(! $isSuperAdmin, fn ($q) => $q->whereHas('office', fn ($o) => $o->whereIn('governorate_id', $govIds)))
                ->when($minYear, fn ($q) => $q->where('year', '>=', $minYear))
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
                'breakdown'   => null,
            ];
        });
        }

        // ملخص المطالبات المالي — محجوب بصلاحية claims.index، مجمّع حسب محافظات المستخدم
        $canViewClaims   = $isSuperAdmin || $user->can('claims.index');
        $claimsDemands   = 0.0;
        $claimsCancelled = 0.0;
        $claimsCollected = 0.0;
        $claimsDebt      = 0.0;
        $claimsRate      = null;
        $govDebtTooltip = collect();
        if ($canViewClaims) {
            $demandsByGov = \App\Models\GovernorateDemand::when(! $isSuperAdmin, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->selectRaw('governorate_id, SUM(amount) as total')->groupBy('governorate_id')->pluck('total', 'governorate_id');
            $cancelledByGov = \App\Models\GovernorateCancelledDemand::when(! $isSuperAdmin, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->selectRaw('governorate_id, SUM(amount) as total')->groupBy('governorate_id')->pluck('total', 'governorate_id');
            $collectedByGov = \App\Models\GovernorateClaim::when(! $isSuperAdmin, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->selectRaw('governorate_id, SUM(value) as total')->groupBy('governorate_id')->pluck('total', 'governorate_id');

            $claimsDemands   = (float) $demandsByGov->sum();
            $claimsCancelled = (float) $cancelledByGov->sum();
            $claimsCollected = (float) $collectedByGov->sum();
            $claimsDebt      = $claimsDemands - $claimsCancelled - $claimsCollected;
            // نسبة التحصيل من صافي المطالبات (بعد خصم الملغاة)
            $netDemands      = $claimsDemands - $claimsCancelled;
            $claimsRate      = $netDemands > 0 ? round($claimsCollected / $netDemands * 100, 1) : null;

            // مديونية كل محافظة بنفس ترتيب رسم المحافظات (للـ tooltip)
            $govDebtTooltip = $officesByGovRaw->values()->map(
                fn ($r) => (float) ($demandsByGov[$r->governorate_id] ?? 0)
                    - (float) ($cancelledByGov[$r->governorate_id] ?? 0)
                    - (float) ($collectedByGov[$r->governorate_id] ?? 0)
            )->values();
        }

        // بيانات نطاق المشرف (level >= 2)
        $teamUserIds   = collect(); // مستخدمو الفريق (يشاركون محافظة)
        $usersAboveMe  = collect(); // مستخدمون مستواهم أعلى مني (لا أراهم)
        $myOfficeIds   = collect(); // مقرات محافظاتي (لنطاق نشاط المقرات)
        $myVehicleIds  = collect(); // سيارات محافظاتي (لنطاق نشاط السيارات)
        if ($isSupervisor) {
            $teamUserIds = User::whereHas('governorates', fn ($q) => $q->whereIn('governorates.id', $govIds))
                ->pluck('id');
            // مستخدمون لا أراهم: مستواهم أعلى مني، أو super-admin (فوق الجميع دائماً)
            $usersAboveMe = User::where(fn ($q) => $q
                    ->whereHas('roles', fn ($r) => $r->where('level', '>', $myLevel))
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'super-admin')))
                ->pluck('id');
            $myOfficeIds  = Office::whereIn('governorate_id', $govIds)->pluck('id');
            $myVehicleIds = Vehicle::whereIn('governorate_id', $govIds)->pluck('id');
        }

        // المتصلون الآن: super-admin يرى الكل، المشرف يرى فريقه، المفتش يرى نفسه فقط
        $onlineUsers = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(10)->timestamp)
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->when($isSupervisor, fn ($q) => $q
                ->whereIn('sessions.user_id', $teamUserIds)
                ->whereNotIn('sessions.user_id', $usersAboveMe))
            ->when(! $isSuperAdmin && ! $isSupervisor, fn ($q) => $q
                ->where('sessions.user_id', $user->id))
            ->select('users.id', 'users.name', 'sessions.last_activity', 'sessions.ip_address')
            ->get();

        $activitiesQuery = Activity::with('causer')
            ->when($this->search, fn ($q) => $q
                ->where('description', 'like', "%{$this->search}%")
                ->orWhereHasMorph('causer', User::class, fn ($u) => $u->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
            ->latest();

        if ($isSupervisor) {
            // المشرف: نشاطه + نشاط المقرات/السيارات في محافظاته + دخول/خروج فريقه — كلها لمستوى ≤ مستواه
            $activitiesQuery->where(function ($q) use ($user, $myOfficeIds, $myVehicleIds, $teamUserIds, $usersAboveMe, $govIds) {
                // نشاطه هو
                $q->where(fn ($w) => $w->where('causer_id', $user->id)->where('causer_type', User::class));

                // نشاط على مقر داخل محافظاته (من مستوى ≤ مستواه)
                $q->orWhere(fn ($w) => $w
                    ->where('subject_type', Office::class)
                    ->whereIn('subject_id', $myOfficeIds)
                    ->whereNotIn('causer_id', $usersAboveMe));

                // نشاط على سيارة داخل محافظاته (من مستوى ≤ مستواه)
                $q->orWhere(fn ($w) => $w
                    ->where('subject_type', Vehicle::class)
                    ->whereIn('subject_id', $myVehicleIds)
                    ->whereNotIn('causer_id', $usersAboveMe));

                // نشاط المطالبات/المحصل في محافظاته (المحافظة مخزّنة في خصائص السجل)
                $q->orWhere(fn ($w) => $w
                    ->whereIn('subject_type', [\App\Models\GovernorateDemand::class, \App\Models\GovernorateClaim::class, \App\Models\GovernorateCancelledDemand::class])
                    ->whereIn('properties->governorate_id', $govIds)
                    ->whereNotIn('causer_id', $usersAboveMe));

                // دخول/خروج أعضاء فريقه (من مستوى ≤ مستواه)
                $q->orWhere(fn ($w) => $w
                    ->whereIn('event', ['login', 'logout'])
                    ->whereIn('causer_id', $teamUserIds)
                    ->whereNotIn('causer_id', $usersAboveMe));
            });
        } elseif (! $isSuperAdmin) {
            // مفتش: نشاطه فقط
            $activitiesQuery->where('causer_id', $user->id)
                            ->where('causer_type', User::class);
        }

        $activities = $activitiesQuery->paginate(10);

        // أسماء المحافظات (lookup لعرض عنصر سجلات المطالبات في سجل النشاط)
        $govNames = Governorate::pluck('name', 'id');

        return view('livewire.dashboard', compact(
            'totalOffices', 'totalGovernorates', 'totalUsers',
            'addedThisMonth', 'needsVisitCount', 'onlineUsers', 'activities', 'isSuperAdmin',
            'officesByGov', 'vehiclesByGov', 'officesByType', 'officesByStructure',
            'user', 'statsSummary', 'govTooltipData', 'govNames',
            'canView', 'canEdit', 'canEditVehicles', 'canViewVehicles', 'isSupervisor', 'canViewOfficeStats',
            'canViewVehicleStats', 'totalVehicles', 'vehiclesWorking', 'vehiclesMaintenance', 'vehiclesStopped',
            'vehicleStatsSummary',
            'canViewClaims', 'claimsDemands', 'claimsCancelled', 'claimsCollected', 'claimsDebt', 'claimsRate', 'govDebtTooltip'
        ));
    }
}
