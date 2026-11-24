<?php

use App\Livewire\FeedbackResults\Messages;
use App\Models\FeedbackMessage;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Support\FeedbackResults\FeedbackAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/** شاشة «شكر الموظفين» — الدخول بـfeedback.view ونطاق المحافظات كباقي الآراء. */
function msgUser(array $abilities, array $governorates = []): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($abilities);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

function thanksIn(Governorate $governorate, array $attributes = []): FeedbackMessage
{
    $office = Office::factory()->public()->create(['governorate_id' => $governorate->id]);

    return FeedbackMessage::factory()->create(['office_id' => $office->id] + $attributes);
}

it('تفتح الشاشة لصاحب feedback.view وتمنعها عمّن لا يملكها', function () {
    $gov = Governorate::factory()->create();

    $this->actingAs(msgUser([FeedbackAccess::VIEW], [$gov]))
        ->get(route('feedback-results.messages', 'thanks'))->assertOk();

    $this->actingAs(msgUser([FeedbackAccess::REJECTED], [$gov]))
        ->get(route('feedback-results.messages', 'thanks'))->assertForbidden();
});

it('يرى رسائل محافظاته وحدها', function () {
    [$mine, $other] = Governorate::factory()->count(2)->create();
    thanksIn($mine, ['body' => 'شكر في محافظتي']);
    thanksIn($other, ['body' => 'شكر في محافظة أخرى']);

    Livewire::actingAs(msgUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->assertSee('شكر في محافظتي')
        ->assertDontSee('شكر في محافظة أخرى');
});

it('صلاحية بلا محافظات لا ترى شيئاً، والسوبر أدمن يرى الكل', function () {
    [$a, $b] = Governorate::factory()->count(2)->create();
    thanksIn($a, ['body' => 'رسالة أولى']);
    thanksIn($b, ['body' => 'رسالة ثانية']);

    Livewire::actingAs(msgUser([FeedbackAccess::VIEW]))
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->assertDontSee('رسالة أولى')->assertDontSee('رسالة ثانية');

    Role::findOrCreate('super-admin', 'web');
    $admin = tap(User::factory()->create())->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->assertSee('رسالة أولى')->assertSee('رسالة ثانية');
});

it('البحث العربي المطبَّع يشمل اسم الموظف ونص الرسالة', function () {
    $gov = Governorate::factory()->create();
    thanksIn($gov, ['employee_name' => 'أسامة إبراهيم', 'body' => 'نص عادي']);
    thanksIn($gov, ['employee_name' => 'محمود', 'body' => 'خدمة مميزة جدا']);

    $screen = Livewire::actingAs(msgUser([FeedbackAccess::VIEW], [$gov]))
        ->test(Messages::class, ['messageType' => 'thanks']);

    $screen->set('search', 'اسامه ابراهيم')->assertSee('نص عادي')->assertDontSee('خدمة مميزة جدا');
    $screen->set('search', 'مميزه')->assertSee('خدمة مميزة جدا')->assertDontSee('نص عادي');
});

it('الحذف الجماعي يحتاج feedback.delete، ومعرّف مدسوس من محافظة أخرى لا يُمسّ', function () {
    [$mine, $other] = Governorate::factory()->count(2)->create();
    $own = thanksIn($mine);
    $foreign = thanksIn($other);

    Livewire::actingAs(msgUser([FeedbackAccess::VIEW], [$mine]))
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->set('selected', [(string) $own->id])
        ->call('deleteSelected')
        ->assertForbidden();

    Livewire::actingAs(msgUser([FeedbackAccess::VIEW, FeedbackAccess::DELETE], [$mine]))
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->set('selected', [(string) $own->id, (string) $foreign->id])
        ->call('deleteSelected');

    expect($own->fresh()->trashed())->toBeTrue()
        ->and($foreign->fresh()->trashed())->toBeFalse();
});

/** صفوف ورقة Excel بلا سطر الرؤوس — يُفحص ما خرج في الملف فعلاً. */
function msgSheet(object $export): array
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
    $rows = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')->load($path)->getSheet(0)->toArray();
    unlink($path);

    return $rows;
}

it('Excel يخرج بنطاق المستخدم وبحثه، وبيانات المواطن مقفولة افتراضياً', function () {
    [$mine, $other] = Governorate::factory()->count(2)->create();
    thanksIn($mine, ['name' => 'سامي علي', 'employee_name' => 'منى حسن', 'body' => 'شكر أول']);
    thanksIn($mine, ['body' => 'شكر ثانٍ']);
    thanksIn($other, ['body' => 'شكر خارج النطاق']);

    $screen = Livewire::actingAs(msgUser([FeedbackAccess::VIEW, FeedbackAccess::EXPORT], [$mine]))
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->set('search', 'منى');

    $rows = msgSheet($screen->instance()->excelExport());
    $flat = implode('|', array_map(fn ($r) => implode('|', $r), $rows));

    expect($rows)->toHaveCount(2)                       // الرأس + الرسالة المطابقة وحدها
        ->and($flat)->toContain('منى حسن')->toContain('شكر أول')
        ->and($flat)->not->toContain('شكر ثانٍ')->not->toContain('شكر خارج النطاق')
        ->and($flat)->not->toContain('سامي علي')->not->toContain('29001010101234');

    $flat = implode('|', array_map(fn ($r) => implode('|', $r), msgSheet($screen->set('exportPersonal', true)->instance()->excelExport())));
    expect($flat)->toContain('سامي علي')->toContain('29001010101234');
});

it('التصدير يحتاج feedback.export داخل الإجراء نفسه', function () {
    $gov = Governorate::factory()->create();
    thanksIn($gov);

    Livewire::actingAs(msgUser([FeedbackAccess::VIEW], [$gov]))
        ->test(Messages::class, ['messageType' => 'thanks'])
        ->call('exportExcel')->assertForbidden();
});

it('سحب صلاحية النوع والشاشة مفتوحة يمنع تصديرها ولو بقيت صلاحية التصدير', function () {
    $gov = Governorate::factory()->create();
    thanksIn($gov);
    $user = msgUser([FeedbackAccess::VIEW, FeedbackAccess::EXPORT], [$gov]);

    $screen = Livewire::actingAs($user)->test(Messages::class, ['messageType' => 'thanks']);

    $user->revokePermissionTo(FeedbackAccess::VIEW);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $screen->call('exportExcel')->assertForbidden();
});

it('يولّد الـPDF لصاحب التصدير ويمنعه عمّن لا يملكه أو لا يفتح النوع', function () {
    $gov = Governorate::factory()->create();
    thanksIn($gov, ['body' => 'رسالة في التقرير']);

    $response = $this->actingAs(msgUser([FeedbackAccess::VIEW, FeedbackAccess::EXPORT], [$gov]))
        ->get(route('feedback-results.messages.pdf', ['messageType' => 'thanks', 'sort' => 'body; drop']));
    $response->assertOk();
    expect($response->getContent())->toStartWith('%PDF');

    $this->actingAs(msgUser([FeedbackAccess::VIEW], [$gov]))
        ->get(route('feedback-results.messages.pdf', 'thanks'))->assertForbidden();

    // صاحب التصدير بلا عرض: النوع مقصور على مَن يفتح شاشته
    $this->actingAs(msgUser([FeedbackAccess::EXPORT, FeedbackAccess::REJECTED], [$gov]))
        ->get(route('feedback-results.messages.pdf', 'thanks'))->assertForbidden();
});

it('الـPDF يعرض رسائل النطاق وحده', function () {
    [$mine, $other] = Governorate::factory()->count(2)->create();
    thanksIn($mine, ['body' => 'داخل النطاق']);
    thanksIn($other, ['body' => 'خارج النطاق']);

    $captured = null;
    \Illuminate\Support\Facades\View::composer('print.feedback-messages-pdf', function ($view) use (&$captured) {
        $captured = $view->getData()['messages'];
    });

    $this->actingAs(msgUser([FeedbackAccess::VIEW, FeedbackAccess::EXPORT], [$mine]))
        ->get(route('feedback-results.messages.pdf', 'thanks'))->assertOk();

    expect($captured->pluck('body')->all())->toBe(['داخل النطاق']);
});

it('القائمة الجانبية تعرض الشاشة لصاحب العرض', function () {
    $gov = Governorate::factory()->create();

    $this->actingAs(msgUser([FeedbackAccess::VIEW], [$gov]))
        ->get(route('feedback-results.dashboard'))
        ->assertSee(route('feedback-results.messages', 'thanks'));
});
