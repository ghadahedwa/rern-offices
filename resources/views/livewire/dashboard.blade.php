<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('livewire.dashboard.welcome')
    @include('livewire.dashboard.kpi-cards')
    @if($canViewVehicleStats)
        @include('livewire.dashboard.vehicles-summary')
    @endif
    @if($canViewClaims)
        @include('livewire.dashboard.claims-summary')
    @endif
    @if($canViewOfficeStats)
        @include('livewire.dashboard.needs-visit')
    @endif
    @include('livewire.dashboard.chart-governorates')
    @if($canViewOfficeStats)
        @include('livewire.dashboard.charts-type-structure')
        @include('livewire.dashboard.stats-summary')
    @endif
    @include('livewire.dashboard.online-users')
    @include('livewire.dashboard.activity-log')

</div>
