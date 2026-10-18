<?php

use App\Livewire\Dashboard;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Reports\VehicleColumns;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15 12:00', 'Africa/Cairo'));

    $this->vehicle = Vehicle::create([
        'governorate_id' => Governorate::factory()->create()->id, 'name' => 'سيارة التقارير',
        'avg_daily_transactions' => 40, 'avg_daily_support' => 7, 'avg_daily_trips' => 3,
    ]);
    $this->tripsId   = VehicleColumns::tripsTypeId();
    $this->instantId = VehicleColumns::instantTripsTypeId();

    foreach ([
        [1, 2025, null, 4000], [1, 2026, null, 900], [1, 2026, 10, 100],
        [$this->tripsId, 2025, 6, 30], [$this->tripsId, 2025, 7, 12],
        [$this->instantId, 2026, 9, 6], [$this->instantId, 2026, 10, 4],
    ] as [$type, $y, $m, $v]) {
        VehicleStat::create(['vehicle_id' => $this->vehicle->id, 'stat_type_id' => $type, 'year' => $y, 'month' => $m, 'value' => $v]);
    }
    $this->vehicle->load('statistics');
});

it('adds the new averages and trips to the custom report columns', function () {
    $c = VehicleColumns::all();

    expect($c['avg_daily_support']['value']($this->vehicle))->toBe(7)
        ->and($c['avg_daily_trips']['value']($this->vehicle))->toBe(3)
        ->and($c['stat_trips']['value']($this->vehicle))->toBe('42 (2025)')
        ->and($c['stat_instant_trips']['value']($this->vehicle))->toBe('10 (2026 حتى الآن)')
        ->and($c['avg_daily_trips']['value'](new Vehicle()))->toBe('—');
});

it('marks the current year as so far and a past year as complete', function () {
    $tx = VehicleColumns::all()['stat_transactions']['value'];
    expect($tx($this->vehicle))->toBe('1,000 (2026 حتى الآن)');

    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-01-02 12:00', 'Africa/Cairo'));
    expect($tx($this->vehicle))->toBe('1,000 (2026)');
});

it('reads the current year in Cairo time, not UTC', function () {
    // ١ يناير ٢٠٢٧ الساعة ١ فجراً بالقاهرة = ٣١ ديسمبر ٢٠٢٦ بـUTC
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-01-01 01:00', 'Africa/Cairo'));

    expect(VehicleColumns::currentYear())->toBe(2027);
});

it('prints the same figures in the comprehensive pdf', function () {
    $html = view('print.multi-vehicle-pdf', [
        'vehicles'   => collect([$this->vehicle->load(['governorate', 'locations', 'brokenDevices'])]),
        'logoBase64' => null,
    ])->render();

    expect($html)
        ->toContain('متوسط الدعم اليومي:</span> <span class="v">7')
        ->toContain('متوسط الانتقال اليومي:</span> <span class="v">3')
        ->toContain('عدد الانتقالات العادية:</span> <span class="v">42 (2025)')
        ->toContain('عدد الانتقالات الفورية:</span> <span class="v">10 (2026 حتى الآن)')
        ->toContain('1,000 (2026 حتى الآن)');
});

it('shows trips on the dashboard and flags the current year', function () {
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    $summary = Livewire::actingAs($admin)->test(Dashboard::class)->viewData('vehicleStatsSummary');

    expect($summary->keys()->all())->toBe([$this->tripsId, $this->instantId, 1, 2, 3])
        ->and($summary[$this->instantId])->toMatchArray(['label' => 'عدد الانتقالات الفورية', 'latestYear' => 2026, 'partial' => true])
        ->and((float) $summary[$this->instantId]['latestTotal'])->toBe(10.0)
        ->and($summary[1])->toMatchArray(['latestYear' => 2026, 'partial' => true])
        ->and((float) $summary[1]['latestTotal'])->toBe(1000.0)
        ->and($summary[1]['prevYear'])->toBe(2025)
        ->and((float) $summary[1]['prevTotal'])->toBe(4000.0)
        ->and($summary[$this->tripsId])->toMatchArray(['latestYear' => 2025, 'partial' => false, 'prevYear' => null]);
});

it('prints the previous year under the latest one on the dashboard card', function () {
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    Livewire::actingAs($admin)->test(Dashboard::class)
        ->assertSeeHtml('2025: <span class="font-medium text-zinc-500 dark:text-zinc-400">4,000</span>');
});
