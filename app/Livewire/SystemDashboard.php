<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('لوحة تحكم النظام')]
class SystemDashboard extends Component
{
    use WithPagination, WithoutUrlPagination;

    public string $search = '';
    public string $filterEvent = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.settings'),
            403
        );
    }

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
        $isSuperAdmin = auth()->user()?->hasRole('super-admin') ?? false;

        $totalUsers   = null;
        $totalRoles   = null;
        $usersByRole  = collect();
        $onlineUsers  = collect();
        $activities   = null;

        if ($isSuperAdmin) {
            $totalUsers  = User::count();
            $totalRoles  = Role::count();
            $usersByRole = Role::withCount('users')
                ->orderByDesc('level')
                ->orderBy('name')
                ->get();

            $onlineUsers = DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(10)->timestamp)
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->select('users.id', 'users.name', 'sessions.last_activity', 'sessions.ip_address')
                ->get();

            $activities = Activity::with('causer')
                ->where(function ($q) {
                    $q->whereIn('subject_type', [User::class, Role::class])
                      ->orWhereIn('event', ['login', 'logout']);
                })
                ->when($this->search, fn ($q) => $q
                    ->where('description', 'like', "%{$this->search}%")
                    ->orWhereHasMorph('causer', User::class, fn ($u) => $u->where('name', 'like', "%{$this->search}%"))
                )
                ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
                ->latest()
                ->paginate(10);
        }

        return view('livewire.system-dashboard', compact(
            'isSuperAdmin', 'totalUsers', 'totalRoles', 'usersByRole', 'onlineUsers', 'activities'
        ));
    }
}
