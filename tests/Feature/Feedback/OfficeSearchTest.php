<?php

use App\Livewire\Feedback\Message;
use App\Livewire\Feedback\Rating;
use App\Livewire\FeedbackResults\Ratings;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Support\FeedbackResults\FeedbackAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * البحث في منسدلتي المحافظة والمقر — في فورمات المواطن وفلاتر شاشات النتائج.
 * المقر بعد المحافظة دائماً (قرار المستخدمة: أسماء المقرات تتكرر بين المحافظات).
 */
function searchOffices(Governorate $gov, array $names): array
{
    return collect($names)->map(fn ($name) => Office::factory()->public()->create(['governorate_id' => $gov->id, 'name' => $name]))->all();
}

it('فورم المواطن: البحث المطبَّع داخل مقرات المحافظة المختارة وحدها', function () {
    [$cairo, $giza] = Governorate::factory()->count(2)->create();
    searchOffices($cairo, ['مكتب توثيق مدينة نصر', 'مكتب توثيق الزمالك']);
    searchOffices($giza, ['مكتب توثيق مدينة نصر الجيزة']);   // اسم مشابه في محافظة أخرى

    $form = Livewire::test(Rating::class)
        ->set('governorate_id', $cairo->id)
        ->set('officeSearch', 'مدينه نصر');   // التاء المربوطة مكتوبة هاءً

    expect($form->instance()->offices()->pluck('name')->all())->toBe(['مكتب توثيق مدينة نصر']);
});

it('فورم المواطن: المقر المختار يبقى في القائمة ولو لم يطابق البحث', function () {
    $gov = Governorate::factory()->create();
    [$zamalek] = searchOffices($gov, ['مكتب توثيق الزمالك', 'مكتب توثيق المعادي']);

    $form = Livewire::test(Message::class, ['messageType' => 'thanks'])
        ->set('governorate_id', $gov->id)
        ->set('office_id', $zamalek->id)
        ->set('officeSearch', 'المعادي');

    expect($form->instance()->offices()->pluck('name')->sort()->values()->all())
        ->toBe(['مكتب توثيق الزمالك', 'مكتب توثيق المعادي']);
});

it('فورم المواطن: تغيير المحافظة يمسح البحث والمقر', function () {
    [$a, $b] = Governorate::factory()->count(2)->create();
    [$office] = searchOffices($a, ['مكتب توثيق الزمالك']);

    Livewire::test(Rating::class)
        ->set('governorate_id', $a->id)->set('officeSearch', 'الزمالك')->set('office_id', $office->id)
        ->set('governorate_id', $b->id)
        ->assertSet('officeSearch', '')
        ->assertSet('office_id', null);
});

it('فورم المواطن: منسدلة المقر تعرض سطر البحث ونتيجته واسم المختار', function () {
    $gov = Governorate::factory()->create();
    [$zamalek] = searchOffices($gov, ['مكتب توثيق الزمالك', 'مكتب توثيق المعادي']);

    Livewire::test(Rating::class)
        ->assertSee('اختر المحافظة أولاً')
        ->set('governorate_id', $gov->id)
        ->assertSee('ابحث باسم المقر...')
        ->assertSeeHtml('wire:model.live.debounce.300ms="officeSearch"')   // السطر مربوط بالبحث فعلاً
        ->set('officeSearch', 'الزمالك')
        ->assertSee('مكتب توثيق الزمالك')->assertDontSee('مكتب توثيق المعادي')
        ->set('officeSearch', 'لا يوجد')
        ->assertSee('لا توجد مقرات بهذا الاسم في المحافظة المختارة.')
        ->set('officeSearch', '')
        ->set('office_id', $zamalek->id)
        ->assertSeeHtml('<span class="">مكتب توثيق الزمالك</span>');
});

it('الفلاتر: المنسدلة تعرض سطر البحث واسم المختار، والاختيار يمسح البحث', function () {
    $gov = Governorate::factory()->create(['name' => 'الإسكندرية']);
    [$zamalek] = searchOffices($gov, ['مكتب توثيق الزمالك']);

    Livewire::actingAs(searchScopedUser([$gov]))
        ->test(Ratings::class)
        ->assertSee(__('home.fr_search_governorate'))
        ->assertSee(__('home.fr_pick_governorate_first'))
        ->set('governorateSearch', 'اسكندر')
        ->set('governorate_id', (string) $gov->id)
        ->assertSet('governorateSearch', '')
        ->set('officeSearch', 'الزمالك')
        ->set('office_id', (string) $zamalek->id)
        ->assertSet('officeSearch', '')
        ->assertSee('مكتب توثيق الزمالك');
});

function searchScopedUser(array $governorates): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(FeedbackAccess::VIEW);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

it('الفلاتر: البحث في المحافظات داخل نطاق المستخدم وحده', function () {
    $alex = Governorate::factory()->create(['name' => 'الإسكندرية']);
    $aswan = Governorate::factory()->create(['name' => 'أسوان']);
    $other = Governorate::factory()->create(['name' => 'الإسماعيلية']);

    $screen = Livewire::actingAs(searchScopedUser([$alex, $aswan]))
        ->test(Ratings::class)
        ->set('governorateSearch', 'اسكندريه');

    expect($screen->instance()->governorateOptions()->pluck('name')->all())->toBe(['الإسكندرية']);

    // بحثٌ يطابق محافظة خارج النطاق لا يُظهرها
    $screen->set('governorateSearch', 'الاسماعيليه');
    expect($screen->instance()->governorateOptions())->toBeEmpty();
});

it('الفلاتر: البحث في المقرات يُبقي المختار ويُمسح بتغيير المحافظة', function () {
    [$gov, $second] = Governorate::factory()->count(2)->create();
    [$zamalek] = searchOffices($gov, ['مكتب توثيق الزمالك', 'مكتب توثيق المعادي']);

    $screen = Livewire::actingAs(searchScopedUser([$gov, $second]))
        ->test(Ratings::class)
        ->set('governorate_id', (string) $gov->id)
        ->set('office_id', (string) $zamalek->id)
        ->set('officeSearch', 'المعادى');   // الياء ألفاً مقصورة

    expect($screen->instance()->officeOptions()->pluck('name')->sort()->values()->all())
        ->toBe(['مكتب توثيق الزمالك', 'مكتب توثيق المعادي']);

    $screen->set('governorate_id', (string) $second->id)->assertSet('officeSearch', '');
});
