<?php

use App\Livewire\Finance\Mechanization;
use App\Livewire\Finance\MonthEntry;
use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\User;
use App\Support\Finance\AmountInWords;
use App\Support\Finance\FinanceCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // ٥ نوفمبر ٢٠٢٦: أكتوبر ونوفمبر بدآ، وديسمبر لم يبدأ.
    // ⚠️ تاريخٌ **في الماضي** لا المستقبل: تقديم الساعة يجعل Livewire يعدّ ملفاته المؤقتة
    //    منتهية فيضيع اسمها الأصلي — والماضي يبقى صالحاً مهما تقدّم الزمن الحقيقي.
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function entryUser(array $abilities = ['finance.view', 'finance.create']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-entry-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

/** فورم شهرٍ صالح جاهز للمراجعة. */
function filledEntry(string $account = 'mechanization', string $amount = '150000', string $number = '1234')
{
    return Livewire::test(MonthEntry::class, ['account' => $account])
        ->set('amount', $amount)
        ->set('orders.0.number', $number)
        ->set('orders.0.issued_on', '2026-11-02')
        ->set('picked.0', [UploadedFile::fake()->image('order.jpg')]);
}

// ── الإدخال ──────────────────────────────────────────────

it('يحفظ أول شهر (أكتوبر) بأمر دفعه وصورته بعد التأكيد', function () {
    $user = entryUser();
    $this->actingAs($user);

    filledEntry(amount: '١٥٠٬٠٠٠')
        ->assertSet('month', '2026-10-01')
        ->call('review')
        ->assertHasNoErrors()
        ->assertSet('reviewing', true)
        ->assertSee(AmountInWords::pounds(150000))
        ->call('save')
        ->assertRedirect();

    $month = FinanceMonth::with('paymentOrders.attachments')->sole();

    expect($month->month->toDateString())->toBe('2026-10-01')
        ->and($month->amount)->toBe(150000)
        ->and($month->created_by)->toBe($user->id)
        ->and($month->account->key)->toBe('mechanization')
        ->and($month->paymentOrders)->toHaveCount(1)
        ->and($month->paymentOrders[0]->number)->toBe('1234');

    $file = $month->paymentOrders[0]->attachments->sole();
    Storage::disk(FinanceAttachment::DISK)->assertExists($file->path);
    expect($file->original_name)->toBe('order.jpg');
});

it('لا يحفظ شيئاً بالمراجعة وحدها', function () {
    $this->actingAs(entryUser());

    filledEntry()->call('review')->assertSet('reviewing', true);

    expect(FinanceMonth::count())->toBe(0);
});

it('يحفظ أكثر من أمر دفع بترتيبها', function () {
    $this->actingAs(entryUser());

    filledEntry()
        ->call('addOrder')
        ->set('orders.1.number', '5678')
        ->set('orders.1.issued_on', '2026-11-04')
        ->set('picked.1', [UploadedFile::fake()->image('order2.png')])
        ->call('save');

    expect(FinanceMonth::sole()->paymentOrders->pluck('number')->all())->toBe(['1234', '5678']);
});

it('يرفض أمر دفع بلا صورة', function () {
    $this->actingAs(entryUser());

    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', '1000')
        ->set('orders.0.number', '1')
        ->set('orders.0.issued_on', '2026-11-02')
        ->call('review')
        ->assertHasErrors('orderFiles.0')
        ->assertSet('reviewing', false);

    expect(FinanceMonth::count())->toBe(0);
});

it('يرفض الكسر والسالب والنص — ولا يحوّل «12.5» إلى 125', function (string $amount) {
    $this->actingAs(entryUser());

    filledEntry(amount: $amount)->call('review')->assertHasErrors('amount');
})->with(['12.5', '-5', 'ألف', '', '1234567890123']);

it('يقبل رقم أمر الدفع أرقاماً فقط ويوحّد العربية', function () {
    $this->actingAs(entryUser());

    filledEntry(number: '12/A')->call('review')
        ->assertHasErrors('orders.0.number')
        ->assertSee(__('home.fin_order_number_digits'));

    filledEntry(number: '١٢٣')->call('save');

    expect(\App\Models\FinancePaymentOrder::sole()->number)->toBe('123');
});

it('يرفض أمر دفع بتاريخ لم يأتِ ورقمين متكررين', function () {
    $this->actingAs(entryUser());

    filledEntry()->set('orders.0.issued_on', '2026-11-06')->call('review')->assertHasErrors('orders.0.issued_on');

    filledEntry()
        ->call('addOrder')
        ->set('orders.1.number', '1234')
        ->set('orders.1.issued_on', '2026-11-04')
        ->set('picked.1', [UploadedFile::fake()->image('b.jpg')])
        ->call('review')
        ->assertHasErrors('orders.1.number');
});

it('يقبل الصور وحدها — ويرفض الـPDF وغيره', function (UploadedFile $file) {
    $this->actingAs(entryUser());

    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('picked.0', [$file])
        ->assertHasErrors('orderFiles.0')
        ->assertSet('orderFiles.0', []);
})->with([
    'PDF'   => fn () => UploadedFile::fake()->create('order.pdf', 50, 'application/pdf'),
    'برنامج' => fn () => UploadedFile::fake()->create('x.exe', 10),
]);

it('يرفض الـPDF عند الحفظ ولو تجاوز منتقي الصور', function () {
    $this->actingAs(entryUser());

    // `orderFiles` تُكتب مباشرةً — الحارس الثاني في قواعد الحفظ لا في المنتقي وحده
    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', '1000')
        ->set('orders.0.number', '1234')
        ->set('orders.0.issued_on', '2026-11-02')
        ->set('orderFiles.0', [UploadedFile::fake()->create('order.pdf', 50, 'application/pdf')])
        ->call('review')
        ->assertHasErrors('orderFiles.0.0')
        ->assertSet('reviewing', false);
});

// ── ترتيب الشهور ─────────────────────────────────────────

it('يجعل الشهر التالي ما بعد آخر مُدخَل ولا يفتح شهراً لم يبدأ', function () {
    $this->actingAs(entryUser());

    filledEntry()->call('save');
    filledEntry()->assertSet('month', '2026-11-01')->call('save');

    expect(FinanceMonth::orderBy('month')->pluck('month')->map->toDateString()->all())
        ->toBe(['2026-10-01', '2026-11-01']);

    // ديسمبر لم يبدأ: لا شهر في الفورم، والحفظ المباشر يُرفض
    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->assertSet('month', null)
        ->assertSee(__('home.fin_entry_not_due', ['month' => 'ديسمبر 2026']));
});

it('يفصل ترتيب كل حساب عن الآخر', function () {
    $this->actingAs(entryUser());

    filledEntry('mechanization')->call('save');

    filledEntry('ministry-of-finance')->assertSet('month', '2026-10-01');
});

it('يرفض الحفظ إن أدخل مستخدمٌ آخر الشهر نفسه في الأثناء', function () {
    $this->actingAs(entryUser());

    $first  = filledEntry(amount: '100');
    $second = filledEntry(amount: '200');

    $first->call('save');
    $second->call('save');

    expect(FinanceMonth::count())->toBe(1)
        ->and(FinanceMonth::sole()->amount)->toBe(100)
        ->and(Storage::disk(FinanceAttachment::DISK)->allFiles(FinanceAttachment::DIRECTORY))->toHaveCount(1);
});

it('يحسب الشهر الجاري بتوقيت القاهرة', function () {
    // ٢٣:٣٠ UTC آخر أكتوبر = ١:٣٠ فجر أول نوفمبر بالقاهرة
    $this->travelTo(CarbonImmutable::parse('2026-10-31 23:30:00', 'UTC'));

    expect(FinanceCalendar::currentMonth()->toDateString())->toBe('2026-11-01');
});

// ── الصلاحيات ────────────────────────────────────────────

it('يمنع الإدخال عمّن لا يملك finance.create', function () {
    $this->actingAs(entryUser(['finance.view']));

    $this->get(route('finance.entry', 'mechanization'))->assertForbidden();

    // ولا يُعرض له زرّ الإدخال
    $this->get(route('finance.mechanization'))->assertDontSee(route('finance.entry', 'mechanization'), false);
});

it('يرفض الحفظ إن سُحبت الصلاحية والفورم مفتوح', function () {
    $user = entryUser();
    $this->actingAs($user);

    $component = filledEntry();
    $user->roles()->detach();
    $user->unsetRelation('roles')->unsetRelation('permissions');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $component->call('save')->assertForbidden();
    expect(FinanceMonth::count())->toBe(0);
});

it('لا يفتح الإدخال لحسابٍ مجهول', function () {
    $this->actingAs(entryUser());

    $this->get('/finance/unknown/entry')->assertNotFound();
});

// ── صفحة الحساب ──────────────────────────────────────────

it('يعرض شهور السنة المالية وإجماليها وزرّ الشهر التالي', function () {
    $this->actingAs(entryUser());

    filledEntry(amount: '150000')->call('save');

    $this->get(route('finance.mechanization'))
        ->assertOk()
        ->assertSee('أكتوبر 2026')
        ->assertDontSee('سبتمبر 2026')
        ->assertSee('150,000')
        ->assertSee(\App\Support\Finance\AmountInWords::pounds(150000))
        ->assertSee(__('home.fin_order_number_short', ['number' => '1234']))
        ->assertSee('2026-11-02')
        ->assertSee(__('home.fin_entered_of', ['entered' => 1, 'count' => 9]))
        ->assertSee(__('home.fin_year_range', ['from' => 'أكتوبر 2026', 'to' => 'يونيو 2027']))
        ->assertSee(__('home.fin_year_partial', ['count' => 9, 'from' => 'أكتوبر 2026']))
        ->assertSee(__('home.fin_next_month_badge'))
        ->assertSee(__('home.fin_awaiting_entry'))
        ->assertSee(route('finance.entry', 'mechanization'), false);
});

it('يطوي الشهور التالية في سطرٍ واحد', function () {
    $this->actingAs(entryUser());

    filledEntry()->call('save');

    // أكتوبر مُدخَل · نوفمبر التالي · ديسمبر–يونيو سبعة مطويّة
    $this->get(route('finance.mechanization'))
        ->assertSee(__('home.fin_later_months', ['count' => 7, 'from' => 'ديسمبر 2026', 'to' => 'يونيو 2027']))
        ->assertDontSee('مارس 2027');
});

it('لا يعرض زرّ الإدخال والشهر التالي لم يبدأ', function () {
    $this->actingAs(entryUser());

    filledEntry()->call('save');
    filledEntry()->call('save'); // نوفمبر — وديسمبر لم يبدأ

    $this->get(route('finance.mechanization'))
        ->assertSee(__('home.fin_opens_on_first'))
        ->assertDontSee(route('finance.entry', 'mechanization'), false)
        ->assertSee(__('home.fin_later_months', ['count' => 7, 'from' => 'ديسمبر 2026', 'to' => 'يونيو 2027']));
});

it('يعرض الحساب الآخر بلا أرقام هذا الحساب', function () {
    $this->actingAs(entryUser());

    filledEntry(amount: '987654')->call('save');

    $this->get(route('finance.ministry-of-finance'))->assertDontSee('987,654');
});

it('يتجاهل سنة مالية تالفة من الرابط', function () {
    $this->actingAs(entryUser());

    Livewire::withQueryParams(['year' => 1999])->test(Mechanization::class)->assertSet('year', 2026);
    Livewire::withQueryParams(['year' => 2030])->test(Mechanization::class)->assertSet('year', 2026);
});

it('يقسم الشهور على السنة المالية يوليو–يونيو', function () {
    expect(array_map(fn ($m) => $m->format('Y-m'), FinanceCalendar::monthsOf(2026)))
        ->toHaveCount(9)
        ->and(FinanceCalendar::monthsOf(2026)[0]->format('Y-m'))->toBe('2026-10')
        ->and(FinanceCalendar::monthsOf(2027)[0]->format('Y-m'))->toBe('2027-07')
        ->and(FinanceCalendar::monthsOf(2027))->toHaveCount(12)
        ->and(FinanceCalendar::fiscalYearOf(CarbonImmutable::parse('2027-06-01')))->toBe(2026)
        ->and(FinanceCalendar::fiscalYearOf(CarbonImmutable::parse('2027-07-01')))->toBe(2027);
});

// ── صورة أمر الدفع ───────────────────────────────────────

it('يعرض صورة أمر الدفع لصاحب العرض وحده', function () {
    $this->actingAs(entryUser());
    filledEntry()->call('save');

    $file = FinanceAttachment::sole();

    $this->get(route('finance.attachments.show', $file))->assertOk();

    $this->actingAs(entryUser(['offices.index']));
    $this->get(route('finance.attachments.show', $file))->assertForbidden();

    auth()->logout();
    $this->get(route('finance.attachments.show', $file))->assertRedirect();
});

it('ينزّل صورة أمر الدفع باسمٍ من بياناتها لا باسم الكاميرا', function () {
    $this->actingAs(entryUser());

    filledEntry(number: '٤٥٦٧')
        ->set('picked.0', [UploadedFile::fake()->image('IMG_20261102_143512.jpg')])
        ->call('save');

    [$first, $second] = FinanceAttachment::orderBy('id')->get()->all();

    expect($first->downloadName())->toBe('حساب الميكنة (الوزير) - أكتوبر 2026 - أمر 4567 - 1.jpg')
        ->and($second->downloadName())->toEndWith(' - 2.jpg')
        // الاسم الأصلي محفوظ للمراجعة
        ->and($second->original_name)->toBe('IMG_20261102_143512.jpg');

    $disposition = $this->get(route('finance.attachments.show', $first))->headers->get('content-disposition');

    expect(rawurldecode($disposition))->toContain('أمر 4567 - 1.jpg')->toStartWith('inline');

    // زرّ «تنزيل» في العارض يطلبه مرفقاً لا معروضاً
    $download = $this->get(route('finance.attachments.show', $first).'?download=1')->headers->get('content-disposition');
    expect($download)->toStartWith('attachment');
});

it('يفتح الصور في عارض الصفحة بأسمائها ويتنقّل بين صور الأمر الواحد', function () {
    $this->actingAs(entryUser());

    filledEntry()->set('picked.0', [UploadedFile::fake()->image('p2.png')])->call('save');

    [$first, $second] = FinanceAttachment::orderBy('id')->get()->all();

    // الرابط الواحد يحمل قائمة صور أمره كلها — العارض يتنقّل بينها
    $this->get(route('finance.mechanization'))
        ->assertSee('openViewer(JSON.parse', false)
        ->assertSee($first->downloadName(), false)
        ->assertSee($second->downloadName(), false)
        ->assertSee('?download=1', false);
});

// ── الشهر بلا حركة ───────────────────────────────────────

it('يحفظ شهراً مبلغه صفر بلا أمر دفع ويفتح ما بعده', function () {
    $this->actingAs(entryUser());

    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', '0')
        ->assertSee(__('home.fin_no_order'))
        ->set('noOrder', true)
        ->call('review')
        ->assertHasNoErrors()
        ->call('save');

    $month = FinanceMonth::sole();
    expect($month->amount)->toBe(0)->and($month->paymentOrders)->toHaveCount(0);

    filledEntry()->assertSet('month', '2026-11-01');

    $this->get(route('finance.mechanization'))->assertSee(__('home.fin_no_order'));
});

it('يرفض «بلا أمر دفع» لمبلغٍ غير الصفر — ولو وصلت من المتصفح', function () {
    $this->actingAs(entryUser());

    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', '0')
        ->set('noOrder', true)
        ->set('amount', '5000')
        ->assertSet('noOrder', false);

    // القيمة مدسوسة مع مبلغ: لا تمرّ
    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('noOrder', true)
        ->set('amount', '5000')
        ->set('noOrder', true)
        ->call('save')
        ->assertHasErrors('noOrder');

    expect(FinanceMonth::count())->toBe(0);
});

it('يطلب أمر الدفع للصفر ما لم يُعلَّم «بلا أمر دفع»', function () {
    $this->actingAs(entryUser());

    Livewire::test(MonthEntry::class, ['account' => 'mechanization'])
        ->set('amount', '0')
        ->call('review')
        ->assertHasErrors(['orders.0.number', 'orderFiles.0']);
});

// ── تنبيهات المراجعة ─────────────────────────────────────

it('لا ينبّه في الشهر العادي', function () {
    $this->actingAs(entryUser());

    filledEntry(amount: '150000')->call('save');

    filledEntry(amount: '160000', number: '2222')->call('review')->assertViewHas('warnings', []);
});

it('ينبّه لرقم أمر دفع مسجَّل من قبل — في أي حساب — ويسمح بالحفظ', function () {
    $this->actingAs(entryUser());

    filledEntry('ministry-of-finance', number: '1234')->call('save');

    filledEntry('mechanization', number: '1234')
        ->call('review')
        ->assertSee(__('home.fin_warn_duplicate_order', ['number' => '1234', 'account' => 'حساب وزارة المالية', 'month' => 'أكتوبر 2026']))
        ->call('save');

    expect(FinanceMonth::count())->toBe(2);
});

it('ينبّه لمبلغ بعيد عن الشهر السابق في الاتجاهين', function (string $amount, string $key) {
    $this->actingAs(entryUser());

    filledEntry(amount: '150000')->call('save');

    filledEntry(amount: $amount, number: '2222')
        ->call('review')
        ->assertSee(__($key, ['factor' => 3, 'month' => 'أكتوبر 2026', 'amount' => '150,000']));
})->with([
    'صفر زائد' => ['1500000', 'home.fin_warn_amount_high'],
    'صفر ناقص' => ['15000', 'home.fin_warn_amount_low'],
]);

it('ينبّه لأمر دفع أقدم من الشهر بأكثر من شهر', function () {
    $this->actingAs(entryUser());

    filledEntry()
        ->set('orders.0.issued_on', '2026-08-20')
        ->call('review')
        ->assertSee(__('home.fin_warn_old_order', ['number' => '1234', 'date' => '2026-08-20', 'month' => 'أكتوبر 2026']));

    // الشهر السابق مباشرةً طبيعي — أمر سبتمبر لشهر أكتوبر
    filledEntry()->set('orders.0.issued_on', '2026-09-15')->call('review')->assertViewHas('warnings', []);
});

// ── التفقيط ──────────────────────────────────────────────

it('يكتب المبلغ بالحروف', function (int $n, string $words) {
    expect(AmountInWords::number($n))->toBe($words);
})->with([
    [0, 'صفر'],
    [101, 'مائة وواحد'],
    [2000, 'ألفان'],
    [3500, 'ثلاثة آلاف وخمسمائة'],
    [412500, 'أربعمائة واثنا عشر ألف وخمسمائة'],
    [850000, 'ثمانمائة وخمسون ألف'],
    [2500000, 'مليونان وخمسمائة ألف'],
]);
