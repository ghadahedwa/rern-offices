<?php

use App\Livewire\Offices\Create;
use App\Livewire\Offices\Show;
use App\Livewire\Reports\DeviceCount;
use App\Models\DeviceType;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Reports\OfficeColumns;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * عدد طفايات الحريق (طلب 2026-11-17): خانة في «الأجهزة والمعدات» بالخطوة الثانية،
 * وعمود في بيان الأجهزة العددي والتقرير المتعدد، ونوعٌ في الأجهزة المعطلة — على نمط UPS.
 */
beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($admin);

    $this->gov = Governorate::factory()->create();
    $this->office = Office::factory()->create([
        'governorate_id' => $this->gov->id,
        'type_id'        => OfficeType::factory()->create()->id,
        'location_description_id' => \App\Models\LocationDescription::create(['name' => 'مبنى حكومي'])->id,
    ]);
});

it('يحفظ عدد الطفايات من الخطوة الثانية ويعيده عند التعديل', function () {
    Livewire::test(Create::class, ['office' => $this->office])
        ->call('goToStep', Create::STEP_SERVICES)
        ->set('fire_extinguishers_count', '4')
        ->call('nextStep')
        ->assertHasNoErrors();

    expect($this->office->fresh()->fire_extinguishers_count)->toBe(4);

    Livewire::test(Create::class, ['office' => $this->office->fresh()])
        ->assertSet('fire_extinguishers_count', '4');
});

it('الخانة الفارغة تُحفظ فارغةً لا صفراً', function () {
    $this->office->update(['fire_extinguishers_count' => 3]);

    Livewire::test(Create::class, ['office' => $this->office])
        ->call('goToStep', Create::STEP_SERVICES)
        ->set('fire_extinguishers_count', '')
        ->call('nextStep');

    expect($this->office->fresh()->fire_extinguishers_count)->toBeNull();
});

it('يظهر العدد في صفحة عرض المقر', function () {
    $this->office->update(['fire_extinguishers_count' => 6]);

    Livewire::test(Show::class, ['office' => $this->office])
        ->set('activeTab', 'services')
        ->assertSee(__('home.fire_extinguishers_count'))
        ->assertSeeHtml('dark:text-zinc-100">6</p>');
});

it('يجمع الطفايات لكل محافظة في بيان الأجهزة العددي', function () {
    $this->office->update(['fire_extinguishers_count' => 5]);
    Office::factory()->create(['governorate_id' => $this->gov->id, 'type_id' => $this->office->type_id, 'fire_extinguishers_count' => 2]);

    Livewire::test(DeviceCount::class)
        ->call('search')
        ->assertViewHas('matrix', fn ($m) => ($m['workingCols']['fire_extinguishers_count'] ?? null) === 'طفايات حريق'
            && $m['sums'][$this->gov->id]['fire_extinguishers_count'] === 7);
});

it('الطفاية نوعٌ في الأجهزة المعطلة', function () {
    expect(DeviceType::where('name', 'طفاية حريق')->exists())->toBeTrue();
});

it('عمود الطفايات في التقرير المتعدد وفي خلية الأجهزة المجمّعة', function () {
    $this->office->update(['fire_extinguishers_count' => 3]);
    $cols = OfficeColumns::all();

    expect(($cols['fire_extinguishers_count']['value'])($this->office->fresh()))->toBe(3)
        ->and(($cols['working_devices']['value'])($this->office->fresh()))->toContain('طفايات حريق: 3');
});
