<?php

use App\Livewire\FeedbackResults\Dashboard;
use App\Livewire\FeedbackResults\Ratings;
use App\Livewire\FeedbackResults\RejectedAttempts;
use App\Livewire\FeedbackResults\Suggestions;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Office;
use App\Models\SuggestionDomain;
use App\Models\SuggestionTopic;
use App\Models\User;
use App\Services\FeedbackGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/** اسم خاص بهذا الملف — دوال Pest عامة، فالتسمية المشتركة تتصادم بين الملفات. */
function bulkAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function bulkTopic(): SuggestionTopic
{
    $domain = SuggestionDomain::firstOrCreate(['key' => 'building'], ['name' => 'المبنى والتجهيزات', 'order' => 1]);

    return SuggestionTopic::create([
        'suggestion_domain_id' => $domain->id,
        'key'                  => 'topic_'.uniqid(),
        'name'                 => 'زيادة مقاعد الانتظار',
        'order'                => 1,
    ]);
}

/* ===================== الحذف إلى السلة ===================== */

it('ينقل التقييمات المحددة إلى سلة المحذوفات بدل مسحها', function () {
    $office = Office::factory()->public()->create();
    $kept   = FeedbackRating::factory()->create(['office_id' => $office->id]);
    $gone   = FeedbackRating::factory()->create(['office_id' => $office->id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('selected', [(string) $gone->id])
        ->call('deleteSelected');

    // اختفى من الاستعلام العادي، لكنه ما زال في الجدول بـ deleted_at
    expect(FeedbackRating::pluck('id')->all())->toBe([$kept->id])
        ->and(FeedbackRating::withTrashed()->find($gone->id)->deleted_at)->not->toBeNull();
});

it('يعرض المحذوف في السلة ويخفيه عن القائمة', function () {
    $office = Office::factory()->public()->create();
    $live   = FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'صف حي']);
    $dead   = FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'صف محذوف']);
    $dead->delete();

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->assertSee('صف حي')->assertDontSee('صف محذوف')
        ->call('toggleTrashed')
        ->assertSee('صف محذوف')->assertDontSee('صف حي');

    expect($live->fresh()->deleted_at)->toBeNull();
});

it('يسترجع الصفوف المحذوفة من السلة', function () {
    $rating = FeedbackRating::factory()->create(['office_id' => Office::factory()->public()->create()->id]);
    $rating->delete();

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->call('toggleTrashed')
        ->set('selected', [(string) $rating->id])
        ->call('restoreSelected');

    expect($rating->fresh()->deleted_at)->toBeNull();
});

it('الحذف النهائي من السلة يمسح الصف وصفوف ربطه بالعناوين', function () {
    $suggestion = FeedbackSuggestion::factory()->create(['office_id' => Office::factory()->public()->create()->id]);
    $suggestion->topics()->attach(bulkTopic()->id);
    $suggestion->delete();

    Livewire::actingAs(bulkAdmin())->test(Suggestions::class)
        ->call('toggleTrashed')
        ->set('selected', [(string) $suggestion->id])
        ->call('forceDeleteSelected');

    expect(FeedbackSuggestion::withTrashed()->find($suggestion->id))->toBeNull()
        ->and(DB::table('feedback_suggestion_topic')->where('feedback_suggestion_id', $suggestion->id)->count())->toBe(0);
});

/* ===================== مودال التأكيد المشترك ===================== */

it('يفتح مودال التأكيد بنص السلة بلا تنبيه أحمر، ثم يحذف عند التأكيد', function () {
    $rating = FeedbackRating::factory()->create(['office_id' => Office::factory()->public()->create()->id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('selected', [(string) $rating->id])
        ->call('askBulkDelete')
        ->assertSet('showDelete', true)
        ->assertSet('deletingPrompt', __('home.fr_bulk_confirm_delete'))
        // الحذف المنطقي قابل للتراجع فلا تنبيه أحمر
        ->assertSet('deletingWarning', '')
        ->assertSee(__('home.fr_bulk_label', ['count' => 1, 'subject' => __('home.fr_ratings')]))
        ->call('deleteRow')
        ->assertSet('showDelete', false);

    expect($rating->fresh()->deleted_at)->not->toBeNull();
});

it('يُظهر تنبيه اللا رجعة في الحذف النهائي وفي المحاولات المرفوضة', function () {
    $suggestion = FeedbackSuggestion::factory()->create(['office_id' => Office::factory()->public()->create()->id]);
    $suggestion->delete();

    Livewire::actingAs(bulkAdmin())->test(Suggestions::class)
        ->call('toggleTrashed')
        ->set('selected', [(string) $suggestion->id])
        ->call('askBulkForceDelete')
        ->assertSet('deletingPrompt', __('home.fr_bulk_confirm_purge'))
        ->assertSet('deletingWarning', __('home.fr_bulk_warning_permanent'));

    $attempt = FeedbackRejectedAttempt::create(['type' => 'rating', 'reason' => 'honeypot']);

    // جدول بلا سلة: حتى الحذف العادي فيه نهائي، فلازم يحمل التنبيه نفسه
    Livewire::actingAs(bulkAdmin())->test(RejectedAttempts::class)
        ->set('selected', [(string) $attempt->id])
        ->call('askBulkDelete')
        ->assertSet('deletingWarning', __('home.fr_bulk_warning_permanent'));
});

it('لا يفتح المودال بلا تحديد', function () {
    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->call('askBulkDelete')
        ->assertSet('showDelete', false);
});

it('لا يحذف شيئاً لو أُغلق المودال قبل التأكيد', function () {
    $rating = FeedbackRating::factory()->create(['office_id' => Office::factory()->public()->create()->id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('selected', [(string) $rating->id])
        ->call('askBulkDelete')
        ->set('showDelete', false)
        ->call('deleteRow');

    expect($rating->fresh()->deleted_at)->toBeNull();
});

/* ===================== القاعدة التي لا تُكسر ===================== */

it('حذف تقييم لا يفتح نافذة منع التكرار لصاحبه', function () {
    $office = Office::factory()->public()->create();

    $rating = FeedbackRating::factory()->create([
        'office_id'   => $office->id,
        'national_id' => '29001011234567',
        'phone'       => '01012345678',
    ]);

    $gate = app(FeedbackGate::class);
    expect($gate->duplicateRetryDate('rating', '29001011234567', '01012345678', $office->id))->not->toBeNull();

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('selected', [(string) $rating->id])
        ->call('deleteSelected');

    // الصف المحذوف يظل حارساً للنافذة — وإلا صار الحذف الإداري إذناً بإعادة الإرسال
    expect($gate->duplicateRetryDate('rating', '29001011234567', '01012345678', $office->id))->not->toBeNull();
});

it('لا يحتسب الصفوف المحذوفة في متوسطات الداشبورد', function () {
    $office = Office::factory()->public()->create();

    FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 5]);
    $outlier = FeedbackRating::factory()->create(['office_id' => $office->id, 'overall_rating' => 1]);
    $outlier->delete();

    $kpis = Livewire::actingAs(bulkAdmin())->test(Dashboard::class)->viewData('kpis');

    expect($kpis['ratings'])->toBe(1)
        ->and($kpis['avg_overall'])->toBe(5.0);
});

/* ===================== نطاق الحذف الجماعي ===================== */

it('«حدّد كل المطابق» يحذف نتائج الفلتر وحدها', function () {
    $target = Office::factory()->public()->create();
    $other  = Office::factory()->public()->create();

    FeedbackRating::factory()->count(3)->create(['office_id' => $target->id, 'governorate_id' => $target->governorate_id]);
    $safe = FeedbackRating::factory()->create(['office_id' => $other->id, 'governorate_id' => $other->governorate_id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('office_id', (string) $target->id)
        ->call('markAllMatching')
        ->call('deleteSelected');

    expect(FeedbackRating::pluck('id')->all())->toBe([$safe->id]);
});

it('يتجاهل معرّفاً محدداً يقع خارج نطاق الفلتر', function () {
    $target = Office::factory()->public()->create();
    $other  = Office::factory()->public()->create();

    $inScope  = FeedbackRating::factory()->create(['office_id' => $target->id, 'governorate_id' => $target->governorate_id]);
    $outScope = FeedbackRating::factory()->create(['office_id' => $other->id, 'governorate_id' => $other->governorate_id]);

    // معرّف مدسوس من العميل لصف لا تعرضه الشاشة أصلاً
    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('office_id', (string) $target->id)
        ->set('selected', [(string) $inScope->id, (string) $outScope->id])
        ->call('deleteSelected');

    expect($inScope->fresh()->deleted_at)->not->toBeNull()
        ->and($outScope->fresh()->deleted_at)->toBeNull();
});

it('لا يفعل شيئاً حين لا يوجد تحديد', function () {
    FeedbackRating::factory()->create(['office_id' => Office::factory()->public()->create()->id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)->call('deleteSelected');

    expect(FeedbackRating::count())->toBe(1);
});

/* ===================== تصفير التحديد ===================== */

it('يمسح التحديد عند تغيّر الفلتر', function () {
    $office = Office::factory()->public()->create();
    $rating = FeedbackRating::factory()->create(['office_id' => $office->id, 'governorate_id' => $office->governorate_id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->set('selected', [(string) $rating->id])
        ->assertSet('selected', [(string) $rating->id])
        ->set('governorate_id', (string) $office->governorate_id)
        ->assertSet('selected', [])
        ->assertSet('selectAllMatching', false);
});

it('يلغي «حدّد كل المطابق» عند أي تعديل يدوي على التحديد', function () {
    $rating = FeedbackRating::factory()->create(['office_id' => Office::factory()->public()->create()->id]);

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->call('markAllMatching')
        ->assertSet('selectAllMatching', true)
        ->set('selected', [(string) $rating->id])
        ->assertSet('selectAllMatching', false);
});

it('يبدّل تحديد صفوف الصفحة دفعةً واحدة', function () {
    $office = Office::factory()->public()->create();
    $ids    = FeedbackRating::factory()->count(3)->create(['office_id' => $office->id])->pluck('id')->all();

    Livewire::actingAs(bulkAdmin())->test(Ratings::class)
        ->call('togglePage', $ids)
        ->assertCount('selected', 3)
        ->call('togglePage', $ids)
        ->assertCount('selected', 0);
});

it('يعرض ترقية التحديد لكل نتائج الفلتر حين تمتلئ الصفحة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->count(20)->create(['office_id' => $office->id]);

    $screen  = Livewire::actingAs(bulkAdmin())->test(Ratings::class);
    $pageIds = $screen->viewData('ratings')->pluck('id')->all();

    // الصفحة ١٥ صفاً من ٢٠ — الترقية هي الطريق الوحيد لحذف ما يتجاوز صفحة
    $screen->call('togglePage', $pageIds)
        ->assertSee(__('home.fr_bulk_select_all_matching', ['total' => 20]))
        ->call('markAllMatching')
        ->call('deleteSelected');

    expect(FeedbackRating::count())->toBe(0);
});

/* ===================== المحاولات المرفوضة: حذف نهائي ===================== */

it('يحذف المحاولات المرفوضة نهائياً بلا سلة', function () {
    $office  = Office::factory()->public()->create();
    $attempt = FeedbackRejectedAttempt::create([
        'type' => 'rating', 'office_id' => $office->id, 'reason' => 'honeypot', 'ip_address' => '10.0.0.1',
    ]);

    Livewire::actingAs(bulkAdmin())->test(RejectedAttempts::class)
        ->assertSet('showTrashed', false)
        ->set('selected', [(string) $attempt->id])
        ->call('deleteSelected');

    expect(FeedbackRejectedAttempt::count())->toBe(0);
});

it('يمنع الاسترجاع على جدول بلا حذف منطقي', function () {
    $attempt = FeedbackRejectedAttempt::create(['type' => 'rating', 'reason' => 'honeypot']);

    Livewire::actingAs(bulkAdmin())->test(RejectedAttempts::class)
        ->set('selected', [(string) $attempt->id])
        ->call('restoreSelected')
        ->assertForbidden();
});

/* ===================== سجل المساءلة ===================== */

it('يسجّل عملية الحذف الجماعي في سجل النشاط بلا تلويث سجل المقرات', function () {
    $admin  = bulkAdmin();
    $office = Office::factory()->public()->create();
    $rating = FeedbackRating::factory()->create(['office_id' => $office->id]);

    Livewire::actingAs($admin)->test(Ratings::class)
        ->set('selected', [(string) $rating->id])
        ->call('deleteSelected');

    $log = Activity::where('log_name', 'feedback')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->event)->toBe('bulk_delete')
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->properties['count'])->toBe(1)
        // بلا subject: سجلّا داشبورد المقرات وإدارة النظام يفلتران على subject_type
        ->and($log->subject_type)->toBeNull();
});
