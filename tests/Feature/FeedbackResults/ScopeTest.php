<?php

use App\Livewire\FeedbackResults\Dashboard;
use App\Livewire\FeedbackResults\Ratings;
use App\Livewire\FeedbackResults\RejectedAttempts;
use App\Livewire\FeedbackResults\Suggestions;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Support\Branch;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\PermissionGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * مستخدم بصلاحيات رأي المواطن مربوط بمحافظات بعينها.
 * الصلاحية تقول «ماذا يفعل» والمحافظة تقول «على أي بيانات» — فهما وسيطان منفصلان.
 */
function scopedUser(array $abilities, array $governorates = []): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($abilities);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

function scopeAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

/** مقر في محافظة بعينها + تقييم عليه. */
function ratingIn(Governorate $governorate): FeedbackRating
{
    $office = Office::factory()->public()->create(['governorate_id' => $governorate->id]);

    return FeedbackRating::factory()->create(['office_id' => $office->id]);
}

/* ===================== الصلاحية تفتح الفرع ===================== */

it('يفتح شاشات النتائج لمن له feedback.view وحدها', function (string $route) {
    $user = scopedUser([FeedbackAccess::VIEW], [Governorate::factory()->create()]);

    $this->actingAs($user)->get(route($route))->assertOk();
})->with([
    'feedback-results.dashboard',
    'feedback-results.ratings',
    'feedback-results.suggestions',
]);

it('يحجب المحاولات المرفوضة عمّن له feedback.view وحدها', function () {
    // شاشة أمنية (سبب الرفض/الـIP) بصلاحية مستقلة — لا تتبع صلاحية العرض
    $user = scopedUser([FeedbackAccess::VIEW], [Governorate::factory()->create()]);

    $this->actingAs($user)->get(route('feedback-results.rejected'))->assertForbidden();
});

it('يفتح المحاولات المرفوضة لمن له feedback.rejected', function () {
    $user = scopedUser([FeedbackAccess::REJECTED], [Governorate::factory()->create()]);

    $this->actingAs($user)->get(route('feedback-results.rejected'))->assertOk();
});

it('يحجب بطاقة المرفوضات في اللوحة عمّن ليس له feedback.rejected', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->public()->create(['governorate_id' => $gov->id]);
    FeedbackRejectedAttempt::create([
        'type' => 'rating', 'office_id' => $office->id, 'reason' => 'duplicate_window',
    ]);

    // الحارس في DashboardReport لا في القالب — وإلا خرجت الأرقام في الملف المصدَّر
    $without = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$gov]))
        ->test(Dashboard::class)->viewData('rejected');
    $with = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW, FeedbackAccess::REJECTED], [$gov]))
        ->test(Dashboard::class)->viewData('rejected');

    expect($without)->toBeEmpty()
        ->and($with['duplicate_window'])->toBe(1);
});

it('يتيح فرع رأي المواطن لصاحب feedback.view', function () {
    $user = scopedUser([FeedbackAccess::VIEW]);

    expect(Branch::canAccess('feedback', $user))->toBeTrue()
        ->and(Branch::accessibleFor($user))->toContain('feedback');
});

it('يحجب الفرع عمّن لا صلاحية له فيه', function () {
    $user = User::factory()->create();

    expect(Branch::canAccess('feedback', $user))->toBeFalse();
});

it('يعتبر دور رأي المواطن محتاجاً لاختيار محافظات', function () {
    // بدونه يُحفظ المستخدم بلا محافظة فيفتح الشاشة ويجدها فارغة أبداً
    expect(PermissionGroups::needsGovernorates([FeedbackAccess::VIEW]))->toBeTrue()
        ->and(PermissionGroups::needsEntity([FeedbackAccess::VIEW]))->toBeFalse();
});

it('يعرض صلاحيات رأي المواطن في شبكة الأدوار تحت عنوان الفرع', function () {
    $grouped = PermissionGroups::group(Permission::all())['home.branch_feedback']['رأي المواطن'];

    expect($grouped->pluck('name')->all())
        ->toEqualCanonicalizing(FeedbackAccess::ALL);
});

/* ===================== نطاق المحافظات ===================== */

it('يرى تقييمات محافظاته وحدها', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    $ratingIn = ratingIn($mine);
    ratingIn($theirs);

    $rows = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Ratings::class)->viewData('ratings');

    expect($rows->pluck('id')->all())->toBe([$ratingIn->id]);
});

it('يرى مقترحات محافظاته وحدها', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    $office = Office::factory()->public()->create(['governorate_id' => $mine->id]);
    $ours   = FeedbackSuggestion::factory()->create(['office_id' => $office->id]);
    FeedbackSuggestion::factory()->create([
        'office_id' => Office::factory()->public()->create(['governorate_id' => $theirs->id])->id,
    ]);

    $rows = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Suggestions::class)->viewData('suggestions');

    expect($rows->pluck('id')->all())->toBe([$ours->id]);
});

it('يرى محاولات مقرات محافظاته دون المحاولات بلا مقر', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    $mineOffice = Office::factory()->public()->create(['governorate_id' => $mine->id]);
    $ours = FeedbackRejectedAttempt::create([
        'type' => 'rating', 'office_id' => $mineOffice->id, 'reason' => 'duplicate_window',
    ]);
    FeedbackRejectedAttempt::create([
        'type' => 'rating', 'reason' => 'duplicate_window',
        'office_id' => Office::factory()->public()->create(['governorate_id' => $theirs->id])->id,
    ]);
    // محاولة بلا مقر (honeypot قبل اختيار المقر) — لا تنتمي لمحافظة فلا تُنسب لمشرف
    FeedbackRejectedAttempt::create(['type' => 'rating', 'reason' => 'honeypot']);

    $rows = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW, FeedbackAccess::REJECTED], [$mine]))
        ->test(RejectedAttempts::class)->viewData('attempts');

    expect($rows->pluck('id')->all())->toBe([$ours->id]);
});

it('يحسب أرقام اللوحة على محافظاته وحدها', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    ratingIn($mine);
    ratingIn($theirs);
    ratingIn($theirs);

    $kpis = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Dashboard::class)->viewData('kpis');

    expect($kpis['ratings'])->toBe(1);
});

it('لا يرى شيئاً إذا كانت له الصلاحية بلا محافظات', function () {
    ratingIn(Governorate::factory()->create());

    $rows = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW]))
        ->test(Ratings::class)->viewData('ratings');

    expect($rows->total())->toBe(0);
});

it('يرى السوبر أدمن كل المحافظات والمحاولات بلا مقر', function () {
    ratingIn(Governorate::factory()->create());
    ratingIn(Governorate::factory()->create());
    FeedbackRejectedAttempt::create(['type' => 'rating', 'reason' => 'honeypot']);

    $admin = scopeAdmin();

    expect(Livewire::actingAs($admin)->test(Ratings::class)->viewData('ratings')->total())->toBe(2)
        ->and(Livewire::actingAs($admin)->test(RejectedAttempts::class)->viewData('attempts')->total())->toBe(1);
});

/* ===================== الفلتر لا يوسّع النطاق ===================== */

it('يتجاهل فلتر محافظة ليست في نطاق المستخدم', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    ratingIn($mine);
    ratingIn($theirs);

    // الفلتر يصل من الـURL — تضييق لا توسيع
    $rows = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Ratings::class)
        ->set('governorate_id', (string) $theirs->id)
        ->viewData('ratings');

    expect($rows->total())->toBe(0);
});

it('يعرض في قائمة الفلتر محافظات المستخدم وحدها', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    $component = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))->test(Ratings::class);

    expect($component->instance()->governorateOptions->pluck('id')->all())->toBe([$mine->id]);
});

it('لا يسرّب أسماء مقرات محافظة خارج النطاق في قائمة الفلتر', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    Office::factory()->public()->create(['governorate_id' => $theirs->id]);

    $component = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Ratings::class)
        ->set('governorate_id', (string) $theirs->id);

    expect($component->instance()->officeOptions)->toBeEmpty();
});

/* ===================== التصدير ===================== */

it('يمنع التصدير عمّن له العرض بلا feedback.export', function () {
    $mine = Governorate::factory()->create();
    ratingIn($mine);

    Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Ratings::class)
        ->call('exportExcel')
        ->assertForbidden();
});

it('يمنع راوتات الـPDF عمّن له العرض بلا feedback.export', function (string $route) {
    $user = scopedUser([FeedbackAccess::VIEW], [Governorate::factory()->create()]);

    $this->actingAs($user)->get(route($route))->assertForbidden();
})->with([
    'feedback-results.dashboard.pdf',
    'feedback-results.ratings.pdf',
    'feedback-results.suggestions.pdf',
]);

it('يقصر الملف المصدَّر على نطاق المستخدم', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    ratingIn($mine);
    ratingIn($theirs);
    ratingIn($theirs);

    $component = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW, FeedbackAccess::EXPORT], [$mine]))
        ->test(Ratings::class);

    // الملف يخرج من نفس استعلام الشاشة — فلو تجاوز النطاق هنا لتسرّب في كل شاشة
    expect($component->instance()->excelExport()->query()->count())->toBe(1);
});

/* ===================== الحذف ===================== */

it('يمنع الحذف الجماعي عمّن له العرض بلا feedback.delete', function () {
    $mine   = Governorate::factory()->create();
    $rating = ratingIn($mine);

    Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Ratings::class)
        ->set('selected', [(string) $rating->id])
        ->call('deleteSelected')
        ->assertForbidden();

    expect(FeedbackRating::count())->toBe(1);
});

it('لا يحذف صفاً خارج نطاق المستخدم ولو دُسّ معرّفه', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    $ours   = ratingIn($mine);
    $others = ratingIn($theirs);

    Livewire::actingAs(scopedUser([FeedbackAccess::VIEW, FeedbackAccess::DELETE], [$mine]))
        ->test(Ratings::class)
        ->set('selected', [(string) $ours->id, (string) $others->id])
        ->call('deleteSelected');

    expect(FeedbackRating::find($ours->id))->toBeNull()
        ->and(FeedbackRating::find($others->id))->not->toBeNull();
});

it('لا يمسّ «حدّد كل المطابق» صفوف محافظة أخرى', function () {
    $mine   = Governorate::factory()->create();
    $theirs = Governorate::factory()->create();

    ratingIn($mine);
    $others = ratingIn($theirs);

    Livewire::actingAs(scopedUser([FeedbackAccess::VIEW, FeedbackAccess::DELETE], [$mine]))
        ->test(Ratings::class)
        ->call('markAllMatching')
        ->call('deleteSelected');

    expect(FeedbackRating::count())->toBe(1)
        ->and(FeedbackRating::find($others->id))->not->toBeNull();
});

it('لا يفتح سلة المحذوفات لمن ليس له feedback.delete ولو طلبها بالرابط', function () {
    $mine   = Governorate::factory()->create();
    $rating = ratingIn($mine);
    $rating->delete();

    $component = Livewire::actingAs(scopedUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Ratings::class)
        ->set('showTrashed', true);

    expect($component->instance()->viewingTrash())->toBeFalse()
        ->and($component->viewData('ratings')->total())->toBe(0);
});
