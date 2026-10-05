<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * حساب بلا أي فرع متاح (دور Boss الفارغ) كان يلفّ /dashboard → /dashboard حتى
 * ERR_TOO_MANY_REDIRECTS (بلاغ العميل من شبكة الوزارة 2026-10-04). الآن: صفحة «لا توجد صلاحيات».
 */
uses(RefreshDatabase::class);

function noAccessUser(array $permissions = []): User
{
    $user = User::factory()->create(['username' => 'hesham']);

    if ($permissions) {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $role = Role::findOrCreate('no-access-tester-'.uniqid(), 'web');
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    }

    return $user;
}

/** يتتبّع التوجيهات يدوياً بسقف — followingRedirects() لا سقف له فيعلّق على الحلقة نفسها. */
function followRedirectsCapped($test, string $url, int $max = 10): array
{
    $visited = [$url];

    for ($i = 0; $i < $max; $i++) {
        $response = $test->get($url);
        if (! $response->isRedirect()) {
            return [$response, $visited];
        }
        $url = $response->headers->get('Location');
        $visited[] = $url;
    }

    throw new RuntimeException('حلقة توجيه: '.implode(' → ', $visited));
}

it('يوجّه الحساب بلا صلاحيات من الداشبورد إلى صفحة «لا توجد صلاحيات» لا إلى نفسه', function () {
    $this->actingAs(noAccessUser());

    $this->get(route('dashboard'))->assertRedirect(route('no-access'));
});

it('ينتهي تتبّع التوجيه من الداشبورد بصفحة تُعرض، بلا حلقة', function () {
    $this->actingAs(noAccessUser());

    [$response, $visited] = followRedirectsCapped($this, route('dashboard'));

    $response->assertOk()
        ->assertSee(__('home.no_access_title'))
        ->assertSee('hesham')
        ->assertSee(__('home.no_access_logout'));
    expect(end($visited))->toBe(route('no-access'));
});

it('يحوّل الحساب المسجَّل من /login إلى صفحة «لا توجد صلاحيات» بلا حلقة', function () {
    // كان المتصفح عالقاً حتى عند فتح /login: الجلسة باقية فيردّه guest إلى /dashboard
    $this->actingAs(noAccessUser());

    [$response] = followRedirectsCapped($this, route('login'));

    $response->assertOk()->assertSee(__('home.no_access_title'));
});

it('يوجّه صاحب فرعٍ غير المقرات إلى فرعه من الداشبورد', function () {
    $this->actingAs(noAccessUser(['meetings.index']));

    $this->get(route('dashboard'))->assertRedirect(route('meetings.index'));
});

it('يوجّه مَن صار له فرع من صفحة «لا توجد صلاحيات» إلى فرعه', function () {
    // أُسندت له صلاحيات والصفحة مفتوحة — لا يعلق عليها بعد إصلاح دوره
    $this->actingAs(noAccessUser(['meetings.index']));

    $this->get(route('no-access'))->assertRedirect(route('meetings.index'));
});

it('لا يلفّ حين يسقط مدخل الفرع إلى الداشبورد لمن لا يدخل المقرات', function () {
    // فرعٌ بلا مدخل مطابق يسقط في entryUrlFor إلى default_route — وهو هنا dashboard
    config([
        'branches.meetings.entries' => ['meetings.index' => 'meetings.view'],
        'branches.meetings.default_route' => 'dashboard',
    ]);
    $this->actingAs(noAccessUser(['meetings.index']));

    [$response, $visited] = followRedirectsCapped($this, route('dashboard'));

    $response->assertOk()->assertSee(__('home.no_access_title'));
    expect(end($visited))->toBe(route('no-access'));

    [$response] = followRedirectsCapped($this, route('no-access'));
    $response->assertOk();
});

it('يحجب صفحة «لا توجد صلاحيات» عن الزائر غير المسجَّل', function () {
    $this->get(route('no-access'))->assertRedirect(route('login'));
});
