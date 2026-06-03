<?php

namespace App\Livewire;

use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

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

        $officesQuery = Office::query();
        if (! $isSuperAdmin) {
            $govIds = $user->governorates()->pluck('governorates.id');
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

        // Bar chart: توزيع المقرات على المحافظات (أعلى 10)
        $officesByGov = (clone $officesQuery)
            ->select('governorate_id', DB::raw('count(*) as total'))
            ->with('governorate:id,name')
            ->groupBy('governorate_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'name'  => $r->governorate?->name ?? '—',
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

        $activities = $activitiesQuery->paginate(25);

        return view('livewire.dashboard', compact(
            'totalOffices', 'totalGovernorates', 'totalUsers',
            'addedThisMonth', 'onlineUsers', 'activities', 'isSuperAdmin',
            'officesByGov', 'officesByType', 'user'
        ));
    }
}
