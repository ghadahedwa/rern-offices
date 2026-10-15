<?php

use App\Livewire\Vehicles\Show;
use App\Livewire\Vehicles\StatTab\Documentation;
use App\Models\Governorate;
use App\Models\StatType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Reports\VehicleColumns;
use App\Support\VehicleTransactionPeriods as Periods;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->vehicle = Vehicle::create(['governorate_id' => Governorate::factory()->create()->id, 'name' => 'سيارة']);
    $this->admin   = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->tripsId = StatType::where('group_key', 'vehicle_trips')->value('id');
});

function statTab(): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::actingAs(test()->admin)->test(Documentation::class, ['vehicle' => test()->vehicle]);
}

/** إدخال رقم معاملات التوثيق من الفورم كما يفعل المستخدم */
function addTx(?string $month, string $year, int $value = 100): \Livewire\Features\SupportTesting\Testable
{
    $t = statTab()->call('openAdd', Periods::STAT_TYPE_ID)->set('formYear', $year);
    if ($month !== null) {
        $t->set('formMonth', $month);
    }

    return $t->set('formValue', (string) $value)->call('save');
}

function txRows(): array
{
    return VehicleStat::where('stat_type_id', Periods::STAT_TYPE_ID)
        ->orderBy('year')->orderBy('month')
        ->get(['year', 'month', 'value'])
        ->map(fn ($r) => [$r->year, $r->month, (int) $r->value])->all();
}

// ── المتوسطات ──

it('saves the three daily averages together', function () {
    statTab()->call('openAvg')
        ->set('avgDaily', '40')->set('avgSupport', '7')->set('avgTrips', '3')
        ->call('saveAvg')->assertHasNoErrors();

    $v = $this->vehicle->fresh();
    expect([$v->avg_daily_transactions, $v->avg_daily_support, $v->avg_daily_trips])->toBe([40, 7, 3]);
});

it('clears an average when emptied and rejects negatives', function () {
    $this->vehicle->update(['avg_daily_support' => 9]);

    statTab()->call('openAvg')->set('avgSupport', '')->call('saveAvg')->assertHasNoErrors();
    expect($this->vehicle->fresh()->avg_daily_support)->toBeNull();

    statTab()->call('openAvg')->set('avgTrips', '-1')->call('saveAvg')->assertHasErrors(['avgTrips']);
});

// ── قاعدة معاملات التوثيق ──

it('stores years up to 2025 as one yearly figure without a month', function () {
    statTab()->call('openAdd', Periods::STAT_TYPE_ID)->set('formYear', '2025')
        ->assertSet('formPeriod', 'transactions')
        ->assertViewHas('monthOptions', []);

    addTx(null, '2025', 5000)->assertHasNoErrors();
    expect(txRows())->toBe([[2025, null, 5000]]);
});

it('offers january–september then october to december for 2026', function () {
    statTab()->call('openAdd', Periods::STAT_TYPE_ID)->set('formYear', '2026')
        ->assertViewHas('monthOptions', fn ($o) => array_map('strval', array_keys($o)) === ['0', '10', '11', '12']);
});

it('stores the 2026 january–september total and the months after it, and sums the year', function () {
    addTx(Periods::PARTIAL, '2026', 900)->assertHasNoErrors();
    addTx('10', '2026', 100)->assertHasNoErrors();
    addTx('11', '2026', 50)->assertHasNoErrors();

    expect(txRows())->toBe([[2026, null, 900], [2026, 10, 100], [2026, 11, 50]])
        ->and(Documentation::transactionYearTotals($this->vehicle))->toBe([2026 => 1050.0]);
});

it('rejects periods that would count a month twice', function (string $year, ?string $month) {
    addTx($month, $year)->assertHasErrors('formMonth');
    expect(txRows())->toBe([]);
})->with([
    'a month of 2026 before october' => ['2026', '5'],
    '2026 without choosing a period'  => ['2026', null],
    '2027 as one yearly figure'       => ['2027', null],
]);

it('applies the period rule itself', function (int $year, ?int $month, bool $allowed) {
    expect(Periods::allows($year, $month))->toBe($allowed);
})->with([
    [2020, null, true], [2025, null, true], [2025, 3, false],
    [2026, null, true], [2026, 9, false], [2026, 10, true], [2026, 12, true],
    [2027, null, false], [2027, 1, true], [2019, null, false], [2027, 13, false],
]);

it('rejects years before 2020', function () {
    addTx(null, '2019')->assertHasErrors('formYear');
});

it('still blocks a forged period when the client switches the form to monthly', function () {
    statTab()->call('openAdd', Periods::STAT_TYPE_ID)
        ->set('formPeriod', 'monthly')          // يفتح كل الشهور في المنسدلة
        ->set('formYear', '2026')->set('formMonth', '5')->set('formValue', '10')
        ->call('save')
        ->assertHasErrors('formMonth');

    expect(txRows())->toBe([]);
});

it('rejects a duplicate period but allows the 2026 total beside its months', function () {
    addTx(Periods::PARTIAL, '2026')->assertHasNoErrors();
    addTx('10', '2026')->assertHasNoErrors();   // كان يُرفض: الفحص القديم يتجاهل الشهر للنوع السنوي
    addTx(Periods::PARTIAL, '2026')->assertHasErrors('formYear');
    addTx('10', '2026')->assertHasErrors('formYear');
    addTx(null, '2025')->assertHasNoErrors();
    addTx(null, '2025')->assertHasErrors('formYear');
});

it('opens a 2026 total row for editing on the january–september choice', function () {
    $row = VehicleStat::create(['vehicle_id' => $this->vehicle->id, 'stat_type_id' => 1, 'year' => 2026, 'month' => null, 'value' => 900]);

    statTab()->call('openEdit', $row->id)
        ->assertSet('formMonth', Periods::PARTIAL)
        ->set('formValue', '950')->call('save')->assertHasNoErrors();

    expect(txRows())->toBe([[2026, null, 950]]);
});

it('clears a month that no longer fits when the year changes', function () {
    statTab()->call('openAdd', Periods::STAT_TYPE_ID)
        ->set('formYear', '2027')->set('formMonth', '5')
        ->set('formYear', '2026')
        ->assertSet('formMonth', '');
});

it('rejects a stat type that is not a vehicle section', function () {
    $officeOnly = StatType::where('group_key', 'claims')->value('id');

    statTab()->call('openAdd', $officeOnly);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('leaves the shared transactions type yearly for offices', function () {
    expect(StatType::find(Periods::STAT_TYPE_ID)->period)->toBe('yearly');
});

// ── الانتقالات الشهرية ──

it('saves monthly trips', function () {
    statTab()->call('openAdd', $this->tripsId)
        ->set('formYear', '2026')->set('formMonth', '3')->set('formValue', '22')
        ->call('save')->assertHasNoErrors();

    expect(VehicleStat::where('stat_type_id', $this->tripsId)->first(['year', 'month', 'value'])->only(['year', 'month']))
        ->toBe(['year' => 2026, 'month' => 3]);
});

// ── العرض ──

it('lists the sections in order: trips, transactions, forms, folders', function () {
    expect(Documentation::orderedTypes()->pluck('group_key')->all())
        ->toBe(['vehicle_trips', 'transactions', 'forms_folders', 'forms_folders']);
});

it('shows the averages, the period labels and the year total on the vehicle page', function () {
    $this->vehicle->update(['avg_daily_transactions' => 40, 'avg_daily_support' => 7, 'avg_daily_trips' => 3]);
    foreach ([[2025, null, 4000], [2026, null, 900], [2026, 10, 100]] as [$y, $m, $v]) {
        VehicleStat::create(['vehicle_id' => $this->vehicle->id, 'stat_type_id' => 1, 'year' => $y, 'month' => $m, 'value' => $v]);
    }

    Livewire::actingAs($this->admin)->test(Show::class, ['vehicle' => $this->vehicle])
        ->set('activeTab', 'statistics')
        ->assertSee(__('home.average_daily_support'))
        ->assertSee(__('home.average_daily_trips'))
        ->assertSee(__('home.vstat_partial_2026'))
        ->assertSee(__('home.vstat_full_year'))
        ->assertSee('أكتوبر')
        ->assertSee(__('home.vehicle_stat_trips'))
        ->assertSee(__('home.vstat_year_total', ['year' => 2026]))
        ->assertSee('1,000');   // ٩٠٠ (يناير–سبتمبر) + ١٠٠ (أكتوبر)
});

it('reports the 2026 total in the last-year report column', function () {
    foreach ([[2025, null, 4000], [2026, null, 900], [2026, 10, 100]] as [$y, $m, $v]) {
        VehicleStat::create(['vehicle_id' => $this->vehicle->id, 'stat_type_id' => 1, 'year' => $y, 'month' => $m, 'value' => $v]);
    }

    $value = VehicleColumns::all()['stat_transactions']['value'];
    $this->travelTo(\Illuminate\Support\Carbon::parse('2027-03-01'));   // سنة ٢٠٢٦ مكتملة
    expect($value($this->vehicle->load('statistics')))->toBe('1,000 (2026)');
});
