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
use App\Models\SuggestionDomain;
use App\Models\SuggestionTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/** اسم خاص بهذا الملف — دوال Pest عامة، فالتسمية المشتركة تتصادم بين الملفات. */
function exportAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function exportTopic(string $name = 'زيادة مقاعد الانتظار'): SuggestionTopic
{
    $domain = SuggestionDomain::firstOrCreate(['key' => 'building'], ['name' => 'المبنى والتجهيزات', 'order' => 1]);

    return SuggestionTopic::create([
        'suggestion_domain_id' => $domain->id,
        'key'                  => 'topic_'.uniqid(),
        'name'                 => $name,
        'order'                => 1,
    ]);
}

/**
 * صفوف ورقة Excel الأولى (بلا سطر الرؤوس) — الطريقة الوحيدة لفحص
 * ما خرج فعلاً في الملف بدل الاكتفاء بأن الاستدعاء لم يرمِ استثناءً.
 */
function sheetRows(object $export, int $sheet = 0): array
{
    $sheets = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $path   = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $sheets);
    $spreadsheet = $reader->load($path);
    $rows        = $spreadsheet->getSheet($sheet)->toArray();
    unlink($path);

    return array_slice($rows, 1);
}

/* ===================== الصلاحية ===================== */

it('يمنع غير السوبر أدمن من راوتات تقارير PDF', function (string $route) {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route($route))->assertForbidden();
})->with([
    'feedback-results.dashboard.pdf',
    'feedback-results.ratings.pdf',
    'feedback-results.suggestions.pdf',
]);

it('يمنع التصدير بعد سحب الدور ولو كانت الشاشة مفتوحة', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);

    // التصدير يصل في طلب مستقل عن mount، فحارس الشاشة وحده لا يكفي
    $admin     = exportAdmin();
    $component = Livewire::actingAs($admin)->test(Ratings::class);

    $admin->removeRole('super-admin');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $component->call('exportExcel')->assertForbidden();
});

/* ===================== بيانات المواطن ===================== */

it('لا يُخرج بيانات المواطن في Excel افتراضياً', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'محمد عبد الله']);

    $export = Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->instance()->excelExport();

    $flat = implode('|', array_map(fn ($r) => implode('|', $r), sheetRows($export)));

    expect($flat)->not->toContain('محمد عبد الله')
        ->and($flat)->not->toContain('29001010101234');
});

it('يُخرج بيانات المواطن عند تفعيل الخانة صراحةً', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'محمد عبد الله']);

    $export = Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->set('exportPersonal', true)
        ->instance()->excelExport();

    $flat = implode('|', array_map(fn ($r) => implode('|', $r), sheetRows($export)));

    expect($flat)->toContain('محمد عبد الله')
        ->and($flat)->toContain('29001010101234');
});

it('لا يعرض خانة بيانات المواطن في اللوحة التجميعية', function () {
    expect(Livewire::actingAs(exportAdmin())->test(Dashboard::class)
        ->instance()->exportHasPersonalData())->toBeFalse();
});

/* ===================== مطابقة الملف للشاشة ===================== */

it('يصدّر الصفوف المفلترة وحدها لا كل الجدول', function () {
    $inGov  = Governorate::factory()->create();
    $outGov = Governorate::factory()->create();

    $inOffice  = Office::factory()->public()->create(['governorate_id' => $inGov->id, 'name' => 'مقر داخل الفلتر']);
    $outOffice = Office::factory()->public()->create(['governorate_id' => $outGov->id, 'name' => 'مقر خارج الفلتر']);

    FeedbackRating::factory()->create(['office_id' => $inOffice->id, 'governorate_id' => $inGov->id]);
    FeedbackRating::factory()->create(['office_id' => $outOffice->id, 'governorate_id' => $outGov->id]);

    $export = Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->set('governorate_id', (string) $inGov->id)
        ->instance()->excelExport();

    $rows = sheetRows($export);

    expect($rows)->toHaveCount(1)
        ->and(implode('|', $rows[0]))->toContain('مقر داخل الفلتر');
});

it('يحترم البحث العربي المطبَّع في الملف المصدَّر', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => 'الخدمه ممتازه']);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => 'لا تعليق']);

    $export = Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->set('search', 'الخدمة')   // بتاء مربوطة — التطبيع هو ما يجعلها تطابق
        ->instance()->excelExport();

    expect(sheetRows($export))->toHaveCount(1);
});

it('يستبعد الصفوف المحذوفة من الملف المصدَّر', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);
    $deleted = FeedbackRating::factory()->create(['office_id' => $office->id]);
    $deleted->delete();

    $export = Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->instance()->excelExport();

    expect(sheetRows($export))->toHaveCount(1);
});

it('يصدّر المحذوف وحده عند فتح سلة المحذوفات', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);
    FeedbackRating::factory()->create(['office_id' => $office->id])->delete();

    $export = Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->set('showTrashed', true)
        ->instance()->excelExport();

    expect(sheetRows($export))->toHaveCount(1);
});

it('يحذّر بدل تصدير نطاق فارغ', function () {
    Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->set('search', 'لا يوجد مثل هذا الاسم')
        ->call('exportExcel')
        ->assertNoRedirect();

    // لا نشاط تصدير مسجَّل لأن التصدير لم يحدث أصلاً
    expect(Activity::where('event', 'export')->count())->toBe(0);
});

/* ===================== المقترحات ===================== */

it('يضع كل عناوين المقترح في خلية واحدة وورقة تفصيلية بصف لكل عنوان', function () {
    $office     = Office::factory()->public()->create();
    $suggestion = FeedbackSuggestion::factory()->create(['office_id' => $office->id]);
    $suggestion->topics()->sync([exportTopic('عنوان أول')->id, exportTopic('عنوان ثانٍ')->id]);

    $export = Livewire::actingAs(exportAdmin())->test(Suggestions::class)
        ->instance()->excelExport();

    $main   = sheetRows($export, 0);
    $detail = sheetRows($export, 1);

    expect($main)->toHaveCount(1)
        ->and(implode('|', $main[0]))->toContain('عنوان أول')->toContain('عنوان ثانٍ')
        // الورقة الثانية: صف لكل (مقترح × عنوان) — الشكل الصالح للجدول المحوري
        ->and($detail)->toHaveCount(2);
});

/* ===================== المحاولات المرفوضة ===================== */

it('يصدّر المحاولات المرفوضة إلى Excel بلا تقرير PDF', function () {
    $office = Office::factory()->public()->create();
    FeedbackRejectedAttempt::create([
        'type' => 'rating', 'national_id' => '29001010101234', 'phone' => '01012345678',
        'office_id' => $office->id, 'reason' => 'duplicate_window', 'ip_address' => '127.0.0.1',
    ]);

    $component = Livewire::actingAs(exportAdmin())->test(RejectedAttempts::class);

    expect($component->instance()->exportHasPdf())->toBeFalse()
        ->and(sheetRows($component->instance()->excelExport()))->toHaveCount(1);
});

/* ===================== تقارير PDF ===================== */

it('يولّد تقرير اللوحة كـ PDF بالفلاتر القادمة في الرابط', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->public()->create(['governorate_id' => $gov->id]);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'governorate_id' => $gov->id]);

    $response = $this->actingAs(exportAdmin())
        ->get(route('feedback-results.dashboard.pdf', ['gov' => $gov->id, 'from' => '2020-01-01']));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('يولّد تقرير التقييمات كـ PDF', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);

    $this->actingAs(exportAdmin())
        ->get(route('feedback-results.ratings.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('يتجاهل عمود ترتيب خارج القائمة البيضاء في رابط الـ PDF', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);

    // اسم العمود يأتي من الرابط: بلا قائمة بيضاء يصير حقن SQL
    $this->actingAs(exportAdmin())
        ->get(route('feedback-results.ratings.pdf', ['sort' => 'name); drop table users;--', 'dir' => 'asc']))
        ->assertOk();
});

it('يتجاهل تاريخاً تالفاً في رابط الـ PDF بدل الانهيار', function () {
    $this->actingAs(exportAdmin())
        ->get(route('feedback-results.dashboard.pdf', ['from' => 'لا-تاريخ', 'to' => '@@@']))
        ->assertOk();
});

it('يتجاهل فلتراً يصل مصفوفةً في الرابط', function () {
    $this->actingAs(exportAdmin())
        ->get(route('feedback-results.dashboard.pdf').'?gov[]=1&office[]=2')
        ->assertOk();
});

/* ===================== سجل المساءلة ===================== */

it('يسجّل عملية التصدير مع بيان خروج بيانات شخصية من عدمه', function () {
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create(['office_id' => $office->id]);

    Livewire::actingAs(exportAdmin())->test(Ratings::class)
        ->set('exportPersonal', true)
        ->call('exportExcel');

    $activity = Activity::where('event', 'export')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['format'])->toBe('excel')
        ->and($activity->properties['personal'])->toBeTrue()
        // بلا subject حتى لا يظهر في سجلّي داشبورد المقرات وإدارة النظام
        ->and($activity->subject_type)->toBeNull();
});
