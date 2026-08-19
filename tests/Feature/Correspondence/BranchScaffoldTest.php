<?php

use App\Models\User;
use App\Support\Branch;
use App\Support\CorrespondenceCounters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function corrUser(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('corr-scaffold-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

/** يستبدل العدّادات بأرقام معلومة — بلا أي واجهة اختبار في كود الإنتاج. */
function fakeCounters(int $inbox = 0, int $drafts = 0, int $assignments = 0): void
{
    app()->instance(CorrespondenceCounters::class, new class($inbox, $drafts, $assignments) extends CorrespondenceCounters
    {
        public function __construct(private int $in, private int $dr, private int $as) {}

        public function inbox(): int { return $this->in; }

        public function drafts(): int { return $this->dr; }

        public function assignments(): int { return $this->as; }
    });
}

// ── الفرع والشاشات ───────────────────────────────────────

it('يفتح شاشات الفرع الخمس لصاحب صلاحياتها', function () {
    $this->actingAs(corrUser(['correspondence.index', 'correspondence.create', 'correspondence.delegate']));

    foreach (['inbox', 'outbox', 'drafts', 'assignments', 'delegations'] as $screen) {
        $this->get(route("correspondence.{$screen}"))->assertOk();
    }
});

it('يمنع من لا يملك صلاحية الصناديق', function () {
    $this->actingAs(corrUser(['offices.index']));

    foreach (['inbox', 'outbox', 'assignments'] as $screen) {
        $this->get(route("correspondence.{$screen}"))->assertForbidden();
    }
});

it('يمنع التفويض عن غير رئيس الجهة', function () {
    // الوارد مسموح والتفويض لا — الحراسة لكل شاشة بصلاحيتها لا للفرع كله
    $this->actingAs(corrUser(['correspondence.index']));

    $this->get(route('correspondence.inbox'))->assertOk();
    $this->get(route('correspondence.delegations'))->assertForbidden();
});

it('يجعل الوارد هو صفحة دخول الفرع', function () {
    $user = corrUser(['correspondence.index']);

    expect(Branch::entryUrlFor('correspondence', $user))->toBe(route('correspondence.inbox'));
});

it('لا يخلط أطراف المراسلات بفرع المراسلات', function () {
    // `correspondence-entities.*` بالشرطة تبقى في «إدارة النظام»، والنمط `correspondence.*` بالنقطة
    $this->actingAs(corrUser(['correspondence.settings']));

    $this->get(route('correspondence-entities.index'))->assertOk();

    expect(Branch::current())->toBe('system');
});

// ── الظرف ────────────────────────────────────────────────

it('يعرض الظرف لصاحب صلاحية الصناديق', function () {
    $this->actingAs(corrUser(['correspondence.index']));

    $this->get(route('correspondence.inbox'))
        ->assertOk()
        ->assertSee(route('correspondence.inbox'));

    expect(app(CorrespondenceCounters::class)->envelopeVisible())->toBeTrue();
});

it('يخفي الظرف عن مستخدم فرع آخر', function () {
    // مستخدم اجتماعات — لا ظرف ولا حتى استعلام
    $this->actingAs(corrUser(['meetings.index']));

    expect(app(CorrespondenceCounters::class)->envelopeVisible())->toBeFalse();
});

it('يخفي الظرف إن لم يكن الراوت مسجَّلاً — الظرف في الـlayout فيُسقط كل شاشة', function () {
    // وقع فعلاً على الإنتاج: نُشر الكود وlم يُعَد بناء route:cache، فوقع النظام كله.
    // بهذا الحارس أسوأ الأثر أيقونة غائبة لا نظام واقف.
    $this->actingAs(corrUser(['correspondence.index']));

    app('router')->setRoutes(new \Illuminate\Routing\RouteCollection);

    expect(app(CorrespondenceCounters::class)->envelopeVisible())->toBeFalse();
});

it('يخفي الظرف عن مدير القوائم المرجعية وحده', function () {
    // correspondence.settings يدير الأطراف ولا صندوق وارد له
    $this->actingAs(corrUser(['correspondence.settings']));

    expect(app(CorrespondenceCounters::class)->envelopeVisible())->toBeFalse();
});

it('يظهر شارة الظرف عند وجود رقم ويخفيها عند الصفر', function () {
    $this->actingAs(corrUser(['correspondence.index']));

    fakeCounters(inbox: 0);
    $this->get(route('correspondence.outbox'))->assertOk()->assertDontSee('bg-red-600', false);

    fakeCounters(inbox: 3);
    $this->get(route('correspondence.outbox'))->assertOk()->assertSee('bg-red-600', false);
});

it('يختصر الرقم الكبير إلى 99+', function () {
    $this->actingAs(corrUser(['correspondence.index']));
    fakeCounters(inbox: 250);

    $this->get(route('correspondence.outbox'))->assertOk()->assertSee('99+');
});

// ── العدّادات ────────────────────────────────────────────

it('يحسب الرقم مرة واحدة في الطلب', function () {
    // الظرف وبند الوارد يقرأان نفس الرقم — واستعلامان لرقم واحد قد يختلفان
    $counters = app(CorrespondenceCounters::class);

    expect($counters)->toBe(app(CorrespondenceCounters::class));   // singleton
});

it('يعطي صفراً قبل إنشاء جداول المكاتبات', function () {
    $this->actingAs(corrUser(['correspondence.index']));
    $counters = app(CorrespondenceCounters::class);

    expect($counters->inbox())->toBe(0)
        ->and($counters->drafts())->toBe(0)
        ->and($counters->assignments())->toBe(0);
});
