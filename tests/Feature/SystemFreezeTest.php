<?php

use App\Livewire\System\Freeze;
use App\Models\User;
use App\Support\SystemFreeze;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/**
 * تجميد النظام (طلب العميل 2026-11-24: شكٌّ في سرقة حسابات) — لا يدخل إلا super-admin،
 * ومَن كان داخلاً يخرج، وبوابة رأي المواطن تبقى تعمل.
 */
uses(RefreshDatabase::class);

function freezeSuperAdmin(): User
{
    $user = User::factory()->create(['username' => 'boss']);
    $user->assignRole(Role::findOrCreate('super-admin', 'web'));

    return $user;
}

function freezeNow(string $contact = 'المستشار / محمد عبده'): void
{
    SystemFreeze::activate(freezeSuperAdmin(), $contact);
}

function freezeEvents(string $event): int
{
    return Activity::where('log_name', SystemFreeze::LOG)->where('event', $event)->count();
}

it('يعرض شاشة الصيانة باسم المرجوع إليه على صفحة الدخول وقت التجميد', function () {
    freezeNow('المستشار / فلان الفلاني');

    $this->get(route('login'))->assertOk()
        ->assertSee(__('home.freeze_title'))
        ->assertSee('المستشار / فلان الفلاني')
        ->assertSee(__('home.freeze_admin_login'))
        // طلب المستشار: بلا «جميع الحقوق محفوظة» ولا سطر البوابة
        ->assertDontSee('جميع الحقوق محفوظة')
        ->assertDontSee(route('feedback'));
});

it('يعرض فورم الدخول العادي حين لا تجميد', function () {
    $this->get(route('login'))->assertOk()->assertDontSee(__('home.freeze_title'));
});

it('يُبقي بوابة رأي المواطن تعمل للزائر وقت التجميد', function () {
    freezeNow();

    $this->get(route('feedback'))->assertOk()->assertDontSee(__('home.freeze_title'));
    $this->get(route('feedback.rating'))->assertOk();
});

it('يُخرج المستخدم الداخل مع أول طلب ويحوّله لشاشة الصيانة ويسجّل ذلك', function () {
    $user = User::factory()->create();
    freezeNow();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(freezeEvents('blocked_session'))->toBe(1);
});

it('يردّ طلب Livewire لمستخدمٍ داخل بشاشة الصيانة لا بالصفحة', function () {
    $user = User::factory()->create();
    freezeNow();

    $this->actingAs($user)->withHeader('X-Livewire', '1')->get(route('dashboard'))
        ->assertStatus(503)
        ->assertSee(__('home.freeze_title'));

    $this->assertGuest();
});

it('يُخرج الموظف الداخل من البوابة العامة ويتركه يكملها كزائر', function () {
    $user = User::factory()->create();
    freezeNow();

    $this->actingAs($user)->get(route('feedback'))->assertOk();
    $this->assertGuest();
});

it('لا يُخرج super-admin وقت التجميد', function () {
    $admin = freezeSuperAdmin();
    SystemFreeze::activate($admin, 'x');

    $this->actingAs($admin)->get(route('system-freeze'))->assertOk();
    $this->assertAuthenticatedAs($admin);
});

it('يرفض دخول غير super-admin بكلمة سر صحيحة ويسجّل المحاولة بعنوانها', function () {
    User::factory()->create(['username' => 'ahmed']);
    freezeNow();

    $this->post(route('login.store'), ['username' => 'ahmed', 'password' => 'password'])
        ->assertSessionHasErrors(['username' => __('home.freeze_login_blocked')]);

    $this->assertGuest();
    expect(freezeEvents('blocked_login'))->toBe(1);
    expect(Activity::where('event', 'blocked_login')->first()->properties['ip'])->not->toBeNull();
});

it('يُبقي كلمة السر الخاطئة خطأً عادياً بلا تسجيل محاولة', function () {
    User::factory()->create(['username' => 'ahmed']);
    freezeNow();

    $this->post(route('login.store'), ['username' => 'ahmed', 'password' => 'wrong'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(freezeEvents('blocked_login'))->toBe(0);
});

it('يسمح بدخول super-admin وقت التجميد', function () {
    freezeNow();

    $this->post(route('login.store'), ['username' => 'boss', 'password' => 'password'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticated();
});

it('يسمح بالدخول العادي بعد فكّ التجميد', function () {
    $admin = freezeSuperAdmin();
    User::factory()->create(['username' => 'ahmed']);
    SystemFreeze::activate($admin, 'x');
    SystemFreeze::deactivate($admin);

    $this->post(route('login.store'), ['username' => 'ahmed', 'password' => 'password'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticated();
});

it('يحذف جلسات المستخدمين عند التفعيل ويُبقي جلسات super-admin', function () {
    config(['session.driver' => 'database']);
    $admin = freezeSuperAdmin();
    $user = User::factory()->create();

    foreach ([['a', $admin->id], ['b', $user->id], ['c', null]] as [$id, $userId]) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
    }

    SystemFreeze::activate($admin, 'x');

    expect(DB::table('sessions')->pluck('id')->sort()->values()->all())->toBe(['a', 'c']);
});

it('يقصر صفحة التجميد على super-admin', function () {
    $this->actingAs(User::factory()->create())->get(route('system-freeze'))->assertForbidden();
});

it('لا يجمّد من الصفحة إلا بعد نافذة التأكيد ثم يفكّ', function () {
    $this->actingAs(freezeSuperAdmin());

    $page = Livewire::test(Freeze::class)->set('contact', 'المستشار / محمد عبده')->call('activate');
    expect(SystemFreeze::isActive())->toBeFalse();

    $page->call('confirmActivate')->assertSet('confirming', true)->call('activate');
    expect(SystemFreeze::isActive())->toBeTrue();
    expect(SystemFreeze::contact())->toBe('المستشار / محمد عبده');

    $page->call('deactivate');
    expect(SystemFreeze::isActive())->toBeFalse();
});

it('يرفض التجميد بلا اسمٍ يُرجع إليه', function () {
    $this->actingAs(freezeSuperAdmin());

    Livewire::test(Freeze::class)->set('contact', '')->call('confirmActivate')
        ->assertHasErrors('contact')->assertSet('confirming', false);
});
