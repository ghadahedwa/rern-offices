<?php

use App\Livewire\Offices\Create;
use App\Livewire\Offices\Show;
use App\Livewire\Offices\VisitReport;
use App\Livewire\Offices\VisitReportsIndex;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeStat;
use App\Models\StatType;
use App\Models\StructuralCondition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * تقريرا الزيارة (المفتش · المستشار): صفحتان مستقلتان وقائمتان في المنيو،
 * بصلاحيات مستقلة عن offices.* ونطاق محافظات كالمقرات.
 */

function vrUser(Governorate $governorate, array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('vr-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync([$governorate->id]);

    return $user->fresh();
}

function vrOffice(array $attributes = []): Office
{
    return Office::factory()->create($attributes + [
        'cleanliness_rating'           => 'good',
        'office_needs'                 => 'نص المفتش السري',
        'counselor_cleanliness_rating' => 'bad',
        'counselor_office_needs'       => 'نص المستشار السري',
    ]);
}

// ── صفحة التقرير ──

it('opens the report page with the report permission alone, without any offices permission', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.counselor-report.edit']);

    $this->actingAs($user)
        ->get(route('offices.visit-report', [$office->id, 'counselor']))
        ->assertOk()
        ->assertSee($office->name)
        ->assertSee(__('home.vr_save'));

    // قيم الخانات يملؤها Livewire في المتصفح لا في الـHTML — تُفحص من حالة المكوّن
    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'counselor'])
        ->assertSet('office_needs', 'نص المستشار السري');
});

it('lists the report in the sidebar for its holder only', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.counselor-report.view']);

    $this->actingAs($user)
        ->get(route('offices.visit-reports', 'counselor'))
        ->assertOk()
        ->assertSee(route('offices.visit-reports', 'counselor'), false)
        ->assertDontSee(route('offices.visit-reports', 'inspector'), false);
});

it('lets the report holder alone into the offices branch', function () {
    $user = vrUser(Governorate::factory()->create(), ['offices.counselor-report.view']);

    expect(\App\Support\Branch::canAccess('offices', $user))->toBeTrue();
});

it('forbids the other report page', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.counselor-report.edit']);

    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'inspector'])
        ->assertForbidden();
});

it('forbids an office outside the user governorates', function () {
    $office = vrOffice();
    $user   = vrUser(Governorate::factory()->create(), ['offices.inspector-report.edit']);

    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'inspector'])
        ->assertForbidden();
});

it('rejects an unknown report type', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.inspector-report.edit']);

    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'boss'])
        ->assertNotFound();
});

it('shows the report read-only to a viewer and refuses a save call', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.inspector-report.view']);

    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'inspector'])
        ->assertSet('office_needs', 'نص المفتش السري')
        ->assertSee(__('home.vr_read_only'))
        ->assertDontSee(__('home.vr_save'))
        ->set('office_needs', 'محاولة كتابة')
        ->call('save')
        ->assertForbidden();

    expect($office->fresh()->office_needs)->toBe('نص المفتش السري');
});

it('saves the counselor report without touching the inspector report', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.counselor-report.edit']);
    $state  = StructuralCondition::create(['name' => 'جيدة']);

    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'counselor'])
        ->assertSet('office_needs', 'نص المستشار السري')
        ->set('visited_at', '2026-09-20')
        ->set('structural_condition_id', $state->id)
        ->set('cleanliness_rating', 'average')
        ->set('office_needs', '')
        ->call('save')
        ->assertHasNoErrors();

    $office->refresh();
    expect($office->counselor_visited_at->format('Y-m-d'))->toBe('2026-09-20')
        ->and($office->counselor_structural_condition_id)->toBe($state->id)
        ->and($office->counselor_cleanliness_rating)->toBe('average')
        ->and($office->counselor_office_needs)->toBeNull()
        ->and($office->cleanliness_rating)->toBe('good')
        ->and($office->office_needs)->toBe('نص المفتش السري');
});

it('rejects a value outside the whitelist', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.inspector-report.edit']);

    Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'inspector'])
        ->set('cleanliness_rating', 'hacked')
        ->call('save')
        ->assertHasErrors('cleanliness_rating');

    expect($office->fresh()->cleanliness_rating)->toBe('good');
});

it('refuses a save after the governorate is taken away with the page open', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.inspector-report.edit']);

    $component = Livewire::actingAs($user)->test(VisitReport::class, ['office' => $office, 'type' => 'inspector']);
    $user->governorates()->sync([]);

    $component->set('office_needs', 'بعد السحب')->call('save')->assertForbidden();
    expect($office->fresh()->office_needs)->toBe('نص المفتش السري');
});

// ── القائمة ──

it('lists only offices in the user governorates', function () {
    $mine   = vrOffice(['name' => 'مقر محافظتي']);
    $other  = vrOffice(['name' => 'مقر محافظة أخرى']);
    $user   = vrUser($mine->governorate, ['offices.inspector-report.view']);

    Livewire::actingAs($user)->test(VisitReportsIndex::class, ['type' => 'inspector'])
        ->assertSee('مقر محافظتي')
        ->assertDontSee('مقر محافظة أخرى');
});

it('forbids the list without the report permission', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.index', 'offices.inspector-report.view']);

    Livewire::actingAs($user)->test(VisitReportsIndex::class, ['type' => 'counselor'])->assertForbidden();
});

it('filters by how long since the report own last visit, counting never-visited as overdue', function () {
    $gov = Governorate::factory()->create();
    $ago = fn (int $months) => now()->subMonths($months)->toDateString();
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر الشهرين', 'counselor_visited_at' => $ago(2)]);
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر الأربعة', 'counselor_visited_at' => $ago(4)]);
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر الثمانية', 'counselor_visited_at' => $ago(8)]);
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر السنتين', 'counselor_visited_at' => $ago(24)]);
    // زاره المفتش حديثاً والمستشار لم يزره قط — الفلتر على تاريخ هذا التقرير وحده
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر المفتش وحده', 'visited_at' => $ago(1)]);
    $user = vrUser($gov, ['offices.counselor-report.view']);

    $seen = function ($component) {
        $html = $component->html();
        return collect(['مقر الشهرين', 'مقر الأربعة', 'مقر الثمانية', 'مقر السنتين', 'مقر المفتش وحده'])
            ->filter(fn ($name) => str_contains($html, $name))->values()->all();
    };

    $component = Livewire::actingAs($user)->test(VisitReportsIndex::class, ['type' => 'counselor']);

    expect($seen($component->set('visit', 'm3')))->toBe(['مقر الأربعة', 'مقر الثمانية', 'مقر السنتين', 'مقر المفتش وحده'])
        ->and($seen($component->set('visit', 'm6')))->toBe(['مقر الثمانية', 'مقر السنتين', 'مقر المفتش وحده'])
        ->and($seen($component->set('visit', 'm12')))->toBe(['مقر السنتين', 'مقر المفتش وحده'])
        ->and($seen($component->set('visit', 'never')))->toBe(['مقر المفتش وحده']);
});

it('searches Arabic office names normalized', function () {
    $gov = Governorate::factory()->create();
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر الإسماعيلية']);
    vrOffice(['governorate_id' => $gov->id, 'name' => 'مقر أسيوط']);
    $user = vrUser($gov, ['offices.counselor-report.view']);

    // الكلمة بإملائها الأصلي (همزة وتاء مربوطة) — تطابق بالتطبيع لا حرفياً
    Livewire::actingAs($user)->test(VisitReportsIndex::class, ['type' => 'counselor'])
        ->set('search', 'الأسماعيلية')
        ->assertSee('مقر الإسماعيلية')->assertDontSee('مقر أسيوط');
});

it('ignores a tampered filter from the URL', function () {
    $office = vrOffice(['name' => 'مقر ظاهر']);
    $user   = vrUser($office->governorate, ['offices.inspector-report.view']);

    // «99999x» بالتحويل المجرّد محافظةٌ غير موجودة فتُفرغ القائمة — فحص ctype_digit يُهمله
    Livewire::withQueryParams(['gov' => '99999x', 'visit' => 'x', 'sort' => 'password'])
        ->actingAs($user)->test(VisitReportsIndex::class, ['type' => 'inspector'])
        ->assertSee('مقر ظاهر');
});

// ── شاشة المقر وفورم التعديل ──

it('drops report tabs from the show page and shows a header button per viewable report', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.view', 'offices.inspector-report.view']);

    $component = Livewire::actingAs($user)->test(Show::class, ['office' => $office]);

    expect(array_keys($component->instance()->tabs()))->toBe(['basic', 'services', 'media']);
    $component->assertSee(route('offices.visit-report', [$office->id, 'inspector']), false)
        ->assertDontSee(route('offices.visit-report', [$office->id, 'counselor']), false)
        ->assertDontSee('نص المفتش السري');
});

it('keeps the edit form to basic, services and media', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.edit', 'offices.inspector-report.edit']);

    $component = Livewire::withQueryParams(['step' => 4])
        ->actingAs($user)->test(Create::class, ['office' => $office]);

    expect(array_keys($component->instance()->steps()))->toBe([1, 2, 3]);
    $component->assertSet('step', Create::STEP_BASIC)
        ->assertDontSee('نص المفتش السري');
});

it('keeps the office statistics when leaving the media step', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.edit']);
    $type   = StatType::create(['name' => 'طلبات السجل', 'period' => 'monthly', 'value_type' => 'count', 'group_key' => 'registry_requests', 'order' => 9]);
    // `stat_type` عمود قديم ما زال NOT NULL في بنية الاختبار — يُكتب مباشرة
    DB::table('office_statistics')->insert([
        'office_id' => $office->id, 'stat_type_id' => $type->id, 'stat_type' => 'registry_requests',
        'year' => 2026, 'month' => 8, 'value' => 40,
    ]);

    Livewire::withQueryParams(['step' => Create::STEP_MEDIA])
        ->actingAs($user)->test(Create::class, ['office' => $office])
        ->call('prevStep')
        ->assertSet('step', Create::STEP_SERVICES);

    expect(OfficeStat::where('office_id', $office->id)->count())->toBe(1);
});

it('pins the saved office name on the edit page, not the unsaved typing', function () {
    $office = vrOffice();
    $user   = vrUser($office->governorate, ['offices.edit']);

    Livewire::actingAs($user)->test(Create::class, ['office' => $office])
        ->assertSeeHtml('title="'.e($office->name).'"')
        ->set('name', 'اسم لم يُحفظ')
        ->assertSeeHtml('title="'.e($office->name).'"')
        ->assertDontSeeHtml('title="اسم لم يُحفظ"');
});

it('lets the offices list choose its row count and ignores a tampered one', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->count(20)->create(['governorate_id' => $gov->id]);
    $user = vrUser($gov, ['offices.index']);

    $rows = fn ($component) => $component->viewData('offices')->perPage();

    expect($rows(Livewire::actingAs($user)->test(\App\Livewire\Offices\Index::class)))->toBe(15)
        ->and($rows(Livewire::withQueryParams(['per' => '50'])->actingAs($user)->test(\App\Livewire\Offices\Index::class)))->toBe(50)
        ->and($rows(Livewire::withQueryParams(['per' => '100000'])->actingAs($user)->test(\App\Livewire\Offices\Index::class)))->toBe(15);
});

it('numbers report list rows continuously across pages', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->count(20)->create(['governorate_id' => $gov->id]);
    $user = vrUser($gov, ['offices.inspector-report.view']);

    Livewire::withQueryParams(['page' => 2])->actingAs($user)
        ->test(VisitReportsIndex::class, ['type' => 'inspector'])
        ->assertSeeHtml('<td class="px-3 py-3 text-zinc-500">16</td>')
        ->assertDontSeeHtml('<td class="px-3 py-3 text-zinc-500">1</td>');
});

it('sorts the offices list by a related column and falls back to newest for an unknown key', function () {
    $gov  = Governorate::factory()->create();
    $bType = \App\Models\OfficeType::factory()->create(['name' => 'ب نوع']);
    $aType = \App\Models\OfficeType::factory()->create(['name' => 'أ نوع']);
    $first  = Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => $bType->id, 'name' => 'مقر ألف', 'created_at' => now()->subDay()]);
    $second = Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => $aType->id, 'name' => 'مقر باء', 'created_at' => now()]);
    $user = vrUser($gov, ['offices.index']);

    $ids = fn ($component) => $component->viewData('offices')->pluck('id')->all();
    $test = fn (array $params) => Livewire::withQueryParams($params)->actingAs($user)->test(\App\Livewire\Offices\Index::class);

    expect($ids($test([])))->toBe([$second->id, $first->id])
        ->and($ids($test(['sort' => 'type'])))->toBe([$second->id, $first->id])
        ->and($ids($test(['sort' => 'type', 'dir' => 'desc'])))->toBe([$first->id, $second->id])
        ->and($ids($test(['sort' => 'name'])))->toBe([$first->id, $second->id])
        // عمود خارج القائمة البيضاء يسقط للافتراضي لا يُمرَّر لـorderBy
        ->and($ids($test(['sort' => 'password'])))->toBe([$second->id, $first->id]);
});
