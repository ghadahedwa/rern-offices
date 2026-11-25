<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

/**
 * دليل التصميم في «إدارة النظام» (طلب المستخدمة 2026-11-25) — سوبر أدمن وحده،
 * والملف يُخدم كما هو من documentation/design-guide.html.
 */
uses(RefreshDatabase::class);

function guideSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('super-admin', 'web'));

    return $user;
}

function guideAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('admin', 'web'));

    return $user;
}

it('يعرض الصفحة للسوبر أدمن بإطار الدليل وبند القائمة', function () {
    $html = $this->actingAs(guideSuperAdmin())->get(route('design-guide'))
        ->assertOk()
        ->assertSee('<iframe src="'.route('design-guide.file').'"', false)
        ->getContent();

    // البند في القائمة الجانبية برابطه
    expect(substr_count($html, 'href="'.route('design-guide').'"'))->toBeGreaterThanOrEqual(1);
});

it('يخدم ملف الدليل نفسه للسوبر أدمن', function () {
    $response = $this->actingAs(guideSuperAdmin())->get(route('design-guide.file'))->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/html')
        ->and(file_get_contents($response->baseResponse->getFile()->getPathname()))
        ->toBe(file_get_contents(base_path('documentation/design-guide.html')));
});

it('يمنع الصفحة والملف عن غير السوبر أدمن', function () {
    $admin = guideAdmin();

    $this->actingAs($admin)->get(route('design-guide'))->assertForbidden();
    $this->actingAs($admin)->get(route('design-guide.file'))->assertForbidden();
});

it('يمنع الصفحة حتى لو سقط حارس الراوت', function () {
    $this->actingAs(guideAdmin());

    Livewire\Livewire::test(\App\Livewire\System\DesignGuide::class)->assertForbidden();
});

it('يُخفي البند عن غير السوبر أدمن داخل فرع إدارة النظام', function () {
    // مَن يدخل الفرع بـoffices.settings يرى لوحته (٢٠٠) — فغياب البند فحصٌ حقيقي لا صفحة ٤٠٣
    $user = User::factory()->create();
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('offices.settings', 'web'));

    $this->actingAs($user)->get(route('system-dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('design-guide').'"', false);
});

it('يحوّل الضيف لشاشة الدخول', function () {
    $this->get(route('design-guide.file'))->assertRedirect(route('login'));
});
