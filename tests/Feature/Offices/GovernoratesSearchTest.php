<?php

use App\Livewire\Governorates\Index;
use App\Models\Governorate;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * بحث شاشة المحافظات مطبَّع بـ ArabicText (كباقي الشاشات) — ويبقى داخل نطاق المستخدم.
 */

function govSearchUser(array $governorateIds): User
{
    Permission::findOrCreate('governorates.index', 'web');
    $role = Role::findOrCreate('gov-'.uniqid(), 'web');
    $role->givePermissionTo('governorates.index');

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync($governorateIds);

    return $user->fresh();
}

it('finds a governorate whatever the alef and taa marbuta spelling', function () {
    $alex  = Governorate::factory()->create(['name' => 'الإسكندرية']);
    $cairo = Governorate::factory()->create(['name' => 'القاهرة']);
    $user  = govSearchUser([$alex->id, $cairo->id]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('search', 'الاسكندريه')
        ->assertSee('الإسكندرية')
        ->assertDontSee('القاهرة');
});

it('searches the supervising counselor name too', function () {
    $giza  = Governorate::factory()->create(['name' => 'الجيزة', 'supervising_counselor' => 'أحمد علي']);
    $cairo = Governorate::factory()->create(['name' => 'القاهرة', 'supervising_counselor' => 'محمود حسن']);
    $user  = govSearchUser([$giza->id, $cairo->id]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('search', 'احمد')
        ->assertSee('الجيزة')
        ->assertDontSee('القاهرة');
});

it('keeps the search inside the user governorates', function () {
    $mine   = Governorate::factory()->create(['name' => 'أسوان']);
    $others = Governorate::factory()->create(['name' => 'أسيوط']);
    $user   = govSearchUser([$mine->id]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('search', 'اس')
        ->assertSee('أسوان')
        ->assertDontSee('أسيوط');
});
