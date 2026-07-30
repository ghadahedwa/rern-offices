<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('a user with access to the offices branch can visit the dashboard', function () {
    $user = User::factory()->create();
    // دخول فرع المقرات مُشتَق من الصلاحيات (config/branches.php) — لا صلاحية فرع مستقلة
    $user->givePermissionTo(Permission::findOrCreate('offices.index'));

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('a user with no branch access is redirected away from the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect();
});
