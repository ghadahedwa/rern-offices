<?php

use App\Livewire\Offices\Create;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Models\WorkingHour;
use App\Models\WorkSystem;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * منسدلات فورم المقر — عطلان على السيرفر:
 * - 2026-10-21: «ساعات العمل» على «اختر» تُرسل '' فيرفضها MySQL ويسقط الحفظ كله.
 * - 2026-10-17 و10-25: «نظام العمل» بقيمة منسدلة من الخطوة الثانية (`not_working`) — إعادة استعمال
 *   Livewire لعناصر الخطوة السابقة بلا مفتاح. الإصلاح مفتاحٌ لكل خطوة + تحقق القيم على السيرفر.
 * ⚠️ الاختبارات على sqlite الذي يقبل '' في عمود رقمي — فالفحص على القيمة المحفوظة لا على الخطأ.
 */
beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($admin);

    $this->office = Office::factory()->create([
        'governorate_id' => Governorate::factory()->create()->id,
        'type_id'        => OfficeType::factory()->create()->id,
        'location_description_id' => \App\Models\LocationDescription::create(['name' => 'مبنى حكومي'])->id,
    ]);
});

it('«ساعات العمل» على «اختر» تُحفظ فارغةً لا نصاً فارغاً', function () {
    $this->office->update(['working_hours_id' => WorkingHour::create(['name' => 'صباحي'])->id]);

    Livewire::test(Create::class, ['office' => $this->office])
        ->set('working_hours_id', '')
        ->call('nextStep')
        ->assertHasNoErrors();

    expect(DB::table('offices')->where('id', $this->office->id)->value('working_hours_id'))->toBeNull();
});

it('يرفض «نظام العمل» بقيمةٍ ليست من خياراته ولا يحفظها', function () {
    $ws = WorkSystem::create(['name' => 'صباحي']);
    $this->office->update(['work_system_id' => $ws->id]);

    Livewire::test(Create::class, ['office' => $this->office])
        ->set('work_system_id', 'not_working')
        ->call('nextStep')
        ->assertHasErrors(['work_system_id' => 'exists'])
        ->assertSet('step', Create::STEP_BASIC);

    expect($this->office->fresh()->work_system_id)->toBe($ws->id);
});

it('يرفض قيماً غريبة في منسدلات الخطوة الأولى الأخرى', function (string $field, string $value) {
    Livewire::test(Create::class, ['office' => $this->office])
        ->set($field, $value)
        ->call('nextStep')
        ->assertHasErrors([$field]);
})->with([
    ['connection_type_id', 'available'],
    ['contractual_status_id', '999999'],
    ['working_days', 'not_working'],
]);

it('يرفض قيماً غريبة في منسدلات الخطوة الثانية ولا يحفظها صامتةً', function (string $field, string $value) {
    Livewire::test(Create::class, ['office' => $this->office])
        ->call('goToStep', Create::STEP_SERVICES)
        ->set($field, $value)
        ->call('nextStep')
        ->assertHasErrors([$field])
        ->assertSet('step', Create::STEP_SERVICES)
        ->assertSee(__('validation.in', ['attribute' => __('validation.attributes.'.$field)]), false);

    expect($this->office->fresh()->{$field})->toBeNull();
})->with([
    ['queue_management_system', '3'],
    ['surveillance_cameras', 'working'],
    ['electricity_meter_type', 'yes'],
]);

it('يحفظ القيم الصحيحة في الخطوتين', function () {
    $ws = WorkSystem::create(['name' => 'صباحي']);

    Livewire::test(Create::class, ['office' => $this->office])
        ->set('work_system_id', $ws->id)
        ->set('working_days', 'full_week')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->set('queue_management_system', 'not_working')
        ->set('electricity_meter_type', 'prepaid')
        ->call('nextStep')
        ->assertHasNoErrors();

    $office = $this->office->fresh();
    expect($office->work_system_id)->toBe($ws->id)
        ->and($office->working_days)->toBe('full_week')
        ->and($office->queue_management_system)->toBe('not_working')
        ->and($office->electricity_meter_type)->toBe('prepaid');
});

it('لكل خطوةٍ مفتاحها فلا تُعاد عناصر الخطوة السابقة في الأخرى', function () {
    Livewire::test(Create::class, ['office' => $this->office])
        ->assertSeeHtml('wire:key="office-step-1"')
        ->assertDontSeeHtml('wire:key="office-step-2"')
        ->call('goToStep', Create::STEP_SERVICES)
        ->assertSeeHtml('wire:key="office-step-2"')
        ->assertDontSeeHtml('wire:key="office-step-1"');
});
