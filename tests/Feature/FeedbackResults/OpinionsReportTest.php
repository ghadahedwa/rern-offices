<?php

use App\Exports\FeedbackOpinionsExport;
use App\Livewire\FeedbackResults\Opinions;
use App\Models\FeedbackRating;
use App\Models\FeedbackSuggestion;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\SuggestionTopic;
use App\Models\User;
use App\Support\FeedbackResults\FeedbackAccess;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/** تقرير «آراء المواطنين»: التقييمات والمقترحات معاً بالمواطن والمقر والرأي المكتوب. */
function opinionsUser(array $abilities, array $governorates): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($abilities);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

function opinionsAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function opinionsOffice(?Governorate $gov = null): Office
{
    return Office::factory()->public()->create(['governorate_id' => ($gov ?? Governorate::factory()->create())->id]);
}

function opinionsSheet(object $export): array
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
    $rows = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')->load($path)->getSheet(0)->toArray();
    unlink($path);

    return $rows;
}

it('يجمع التقييمات والمقترحات في جدول واحد بالرأي المكتوب والمقر والمحافظة', function () {
    $gov    = Governorate::factory()->create(['name' => 'المنوفية']);
    $office = opinionsOffice($gov);
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'منى سالم', 'notes' => 'الموظف متعاون جداً']);
    FeedbackSuggestion::factory()->create(['office_id' => $office->id, 'name' => 'حسن علي', 'other_suggestion' => 'زيادة عدد الشبابيك']);

    Livewire::actingAs(opinionsAdmin())->test(Opinions::class)
        ->assertSee('الموظف متعاون جداً')
        ->assertSee('زيادة عدد الشبابيك')
        ->assertSee('منى سالم')
        ->assertSee('حسن علي')
        ->assertSee($office->name)
        ->assertSee('المنوفية')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 2);
});

it('يعرض الصفوف بلا نص مكتوب أيضاً، والمقترح بعناوينه المختارة', function () {
    $office = opinionsOffice();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => null]);
    $suggestion = FeedbackSuggestion::factory()->create(['office_id' => $office->id, 'other_suggestion' => null]);
    $domain     = \App\Models\SuggestionDomain::create(['key' => 'd', 'name' => 'مجال', 'order' => 1]);
    $topic      = SuggestionTopic::create(['suggestion_domain_id' => $domain->id, 'key' => 't', 'name' => 'توفير أماكن انتظار', 'order' => 1]);
    $suggestion->topics()->attach($topic->id);

    Livewire::actingAs(opinionsAdmin())->test(Opinions::class)
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 2)
        ->assertSee(__('home.fr_no_text'))
        ->assertSee($topic->name);
});

it('يفلتر بالنوع والفترة', function () {
    $office = opinionsOffice();
    $this->travelTo(CarbonImmutable::parse('2026-03-10 10:00'));
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => 'تقييم مارس']);
    $this->travelTo(CarbonImmutable::parse('2026-05-10 10:00'));
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => 'تقييم مايو']);
    FeedbackSuggestion::factory()->create(['office_id' => $office->id, 'other_suggestion' => 'اقتراح مايو']);

    $test = Livewire::actingAs(opinionsAdmin())->test(Opinions::class);

    $test->set('type', 'rating')
        ->assertSee('تقييم مايو')->assertDontSee('اقتراح مايو');

    $test->set('type', 'suggestion')
        ->assertSee('اقتراح مايو')->assertDontSee('تقييم مايو');

    $test->set('type', '')->set('from', '2026-05-01')->set('to', '2026-05-31')
        ->assertSee('تقييم مايو')->assertSee('اقتراح مايو')->assertDontSee('تقييم مارس');

    // نوعٌ مدسوس في الرابط = الكل لا جدولٌ فارغ
    $test->set('from', '')->set('to', '')->set('type', 'x')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 3);
});

it('يقصر الآراء على محافظات المستخدم', function () {
    $mine  = Governorate::factory()->create();
    $other = Governorate::factory()->create();
    FeedbackRating::factory()->create(['office_id' => opinionsOffice($mine)->id, 'notes' => 'رأي محافظتي']);
    FeedbackSuggestion::factory()->create(['office_id' => opinionsOffice($other)->id, 'other_suggestion' => 'رأي محافظة أخرى']);

    Livewire::actingAs(opinionsUser([FeedbackAccess::VIEW], [$mine]))->test(Opinions::class)
        ->assertSee('رأي محافظتي')
        ->assertDontSee('رأي محافظة أخرى');
});

it('يستبعد المحذوف (السلة)', function () {
    $office = opinionsOffice();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => 'رأي محذوف'])->delete();

    Livewire::actingAs(opinionsAdmin())->test(Opinions::class)->assertDontSee('رأي محذوف');
});

it('يحجب الشاشة عمّن لا feedback.view له، والـPDF عمّن لا feedback.export له', function () {
    $gov = Governorate::factory()->create();

    $this->actingAs(opinionsUser([FeedbackAccess::REJECTED], [$gov]))
        ->get(route('feedback-results.opinions'))->assertForbidden();

    $this->actingAs(opinionsUser([FeedbackAccess::VIEW], [$gov]))
        ->get(route('feedback-results.opinions.pdf'))->assertForbidden();
});

it('ملف Excel بالرأي المكتوب، وبيانات المواطن فيه بالخانة وحدها', function () {
    $office = opinionsOffice();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'name' => 'منى سالم', 'notes' => 'رأي في الملف']);

    $component = Livewire::actingAs(opinionsAdmin())->test(Opinions::class);

    $closed = opinionsSheet($component->instance()->excelExport());
    expect(collect($closed)->flatten()->all())->toContain('رأي في الملف')->not->toContain('منى سالم');

    $component->set('exportPersonal', true);
    $open = opinionsSheet($component->instance()->excelExport());
    expect(collect($open)->flatten()->all())->toContain('منى سالم')->toContain($office->name);
});

it('يولّد الـPDF بفلاتر الشاشة', function () {
    $office = opinionsOffice();
    FeedbackRating::factory()->create(['office_id' => $office->id, 'notes' => 'رأي']);

    $response = $this->actingAs(opinionsAdmin())
        ->get(route('feedback-results.opinions.pdf', ['type' => 'rating', 'personal' => 1]));

    $response->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('نوعٌ مدسوس في رابط الـPDF يُقرأ «الكل» لا عنواناً بمفتاح لغة مفقود', function () {
    FeedbackRating::factory()->create(['office_id' => opinionsOffice()->id, 'notes' => 'رأي']);

    $captured = null;
    \Illuminate\Support\Facades\View::composer('print.feedback-opinions-pdf', function ($view) use (&$captured) {
        $captured = $view->getData()['type'];
    });

    $this->actingAs(opinionsAdmin())
        ->get(route('feedback-results.opinions.pdf', ['type' => 'xyz']))
        ->assertOk();

    expect($captured)->toBe('');
});
