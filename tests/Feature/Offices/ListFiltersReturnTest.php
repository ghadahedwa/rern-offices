<?php

use App\Livewire\Offices\Create;
use App\Livewire\Offices\Index;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Models\WorkSystem;
use App\Support\ListReturnUrl;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * الرجوع من صفحة المقر إلى القائمة بفلاترها (طلب العميل 2026-10-25):
 * كل فلاتر القائمة في الرابط، وأزرار الرجوع تقرأ آخر رابطٍ حفظته القائمة.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($this->admin);

    $this->gov    = Governorate::factory()->create();
    $this->type   = OfficeType::factory()->create();
    $this->ws     = WorkSystem::create(['name' => 'نظام صباحي']);
    $this->office = Office::factory()->create(['governorate_id' => $this->gov->id, 'type_id' => $this->type->id]);
});

/** كل فلتر بقيمة غير افتراضية — بأسماء الرابط */
function lfrFullQuery($test): array
{
    return [
        'q'              => 'توثيق',
        'governorate_id' => $test->gov->id,
        'type_id'        => $test->type->id,
        'needs_visit'    => 1,
        'is_vip'         => 1,
        'ws'             => $test->ws->id,
        'wd'             => 'full_week',
        'cam'            => 'broken',
        'qms'            => 'working',
        'sort'           => 'name',
        'dir'            => 'desc',
        'per'            => '50',
    ];
}

it('يحفظ كل فلاتر القائمة ويرجع إليها كما هي', function () {
    $query = lfrFullQuery($this);

    Livewire::withQueryParams($query)->test(Index::class);

    expect(ListReturnUrl::query('offices.index'))->toEqual($query);

    // رحلة الذهاب والعودة: الرابط المحفوظ يفتح القائمة على الفلاتر نفسها (يمسك اختلاف مفتاحٍ بين `#[Url]` والمحفوظ)
    Livewire::withQueryParams(ListReturnUrl::query('offices.index'))->test(Index::class)
        ->assertSet('search', 'توثيق')
        ->assertSet('governorate_id', $this->gov->id)
        ->assertSet('type_id', $this->type->id)
        ->assertSet('needs_visit', true)
        ->assertSet('is_vip', true)
        ->assertSet('work_system_id', $this->ws->id)
        ->assertSet('working_days', 'full_week')
        ->assertSet('surveillance_cameras', 'broken')
        ->assertSet('queue_management_system', 'working')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDir', 'desc')
        ->assertSet('perPage', '50');
});

it('يحفظ رقم الصفحة', function () {
    Office::factory()->count(20)->create(['governorate_id' => $this->gov->id, 'type_id' => $this->type->id]);

    Livewire::withQueryParams(['page' => 2])->test(Index::class);

    expect(ListReturnUrl::query('offices.index'))->toEqual(['page' => 2]);
});

it('يحدّث الرابط المحفوظ مع كل تغيير فلتر', function () {
    Livewire::test(Index::class)
        ->set('search', 'شهر')
        ->set('work_system_id', $this->ws->id);

    expect(ListReturnUrl::query('offices.index'))->toEqual(['q' => 'شهر', 'ws' => $this->ws->id]);
});

it('يفتح لوحة البحث المتقدم حين يصل فلترٌ متقدم في الرابط', function () {
    Livewire::withQueryParams(['ws' => $this->ws->id])->test(Index::class)->assertSet('showAdvanced', true);
    Livewire::withQueryParams([])->test(Index::class)->assertSet('showAdvanced', false);
});

it('زرّا الرجوع في صفحات المقر يحملان الفلاتر', function (string $route) {
    Livewire::withQueryParams(['q' => 'توثيق', 'governorate_id' => $this->gov->id])->test(Index::class);

    $back = e(ListReturnUrl::back('offices.index'));
    expect($back)->toContain('governorate_id='.$this->gov->id);

    $this->get(route($route, $this->office))->assertOk()->assertSee('href="'.$back.'"', false);
})->with(['offices.show', 'offices.edit', 'offices.statistics']);

it('بلا قائمةٍ سابقة يرجع للقائمة بلا فلاتر', function () {
    expect(ListReturnUrl::back('offices.index'))->toBe(route('offices.index'));
});

it('الحفظ والخروج من فورم المقر يرجع للقائمة بفلاترها', function () {
    Livewire::withQueryParams(['governorate_id' => $this->gov->id])->test(Index::class);

    Livewire::test(Create::class, ['office' => $this->office])
        ->call('save')
        ->assertRedirect(route('offices.index', ['governorate_id' => $this->gov->id]));
});

it('قيمة تالفة لفلترٍ متقدم في الرابط لا تُسقط الصفحة', function () {
    $this->get(route('offices.index', ['ws' => 'abc']))->assertOk();
});
