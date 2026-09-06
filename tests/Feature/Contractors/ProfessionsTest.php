<?php

use App\Livewire\Contractors\Professions\Create;
use App\Livewire\Contractors\Professions\Index;
use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\Profession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function professionUser(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('ct-prof-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

// ── الصفات المزروعة ──────────────────────────────────────

it('يزرع الصفات الخمس و«مدخل بيانات» وحدها أساسية', function () {
    $seeded = Profession::ordered()->get();

    expect($seeded->pluck('name')->all())->toBe(['مدخل بيانات', 'مترجم', 'عامل', 'سائق', 'مساحي'])
        ->and($seeded->every->is_active)->toBeTrue()
        ->and($seeded->where('is_system', true)->pluck('name')->all())->toBe(['مدخل بيانات']);
});

// ── الحراسة ──────────────────────────────────────────────

it('يفتح الشاشة لصاحب إعدادات العاملين بالتعاقد', function () {
    $this->actingAs(professionUser(['contractors.settings']));

    $this->get(route('professions.index'))->assertOk();
    $this->get(route('professions.create'))->assertOk();
});

it('يمنع الشاشة عمّن لا يملك الإعدادات', function () {
    // ⚠️ صاحب الفرع نفسه لا يديره: القوائم المرجعية تحت «إدارة النظام»
    $this->actingAs(professionUser(['contractors.index', 'contractors.edit']));

    $this->get(route('professions.index'))->assertForbidden();
    $this->get(route('professions.create'))->assertForbidden();
});

// ── الإضافة والتعديل ─────────────────────────────────────

it('يضيف صفةً جديدة ويمنع تكرار الاسم', function () {
    $this->actingAs(professionUser(['contractors.settings']));

    Livewire::test(Create::class)
        ->set('name', 'فني شبكات')
        ->set('order', 9)
        ->call('save')
        ->assertHasNoErrors();

    expect(Profession::where('name', 'فني شبكات')->exists())->toBeTrue();

    Livewire::test(Create::class)
        ->set('name', 'مترجم')
        ->call('save')
        ->assertHasErrors('name');
});

it('لا يُعطِّل الصفة الأساسية ولو أُرسلت معطَّلة', function () {
    // ⚠️ الحارس في الإجراء لا في القالب: الخانة مخفيّة عن الأساسية والقيمة تصل بلا خانة
    $this->actingAs(professionUser(['contractors.settings']));

    $system = Profession::where('is_system', true)->first();

    Livewire::test(Create::class, ['profession' => $system])
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->is_active)->toBeTrue();
});

// ── الحذف ────────────────────────────────────────────────

it('يحذف صفةً بلا عاملين', function () {
    $this->actingAs(professionUser(['contractors.settings']));

    $profession = Profession::create(['name' => 'فني شبكات', 'order' => 9]);

    Livewire::test(Index::class)
        ->call('askDelete', $profession->id)
        ->call('deleteRow');

    expect(Profession::find($profession->id))->toBeNull();
});

it('يمنع حذف الصفة الأساسية', function () {
    $this->actingAs(professionUser(['contractors.settings']));

    $system = Profession::where('is_system', true)->first();

    Livewire::test(Index::class)
        ->call('askDelete', $system->id)
        ->call('deleteRow');

    expect(Profession::find($system->id))->not->toBeNull();
});

it('يمنع حذف صفةٍ عليها عاملون', function () {
    // ⚠️ وإلا فقد عاملوها صفتهم بلا إشعار — والحارس في الإجراء لا في القالب
    $this->actingAs(professionUser(['contractors.settings']));

    $profession = Profession::create(['name' => 'فني شبكات', 'order' => 9]);
    $office     = Office::factory()->create(['governorate_id' => Governorate::factory()]);

    $contractor = Contractor::factory()->create(['profession_id' => $profession->id]);
    ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => '2026-09-01',
    ]);

    Livewire::test(Index::class)
        ->call('askDelete', $profession->id)
        ->call('deleteRow');

    expect(Profession::find($profession->id))->not->toBeNull()
        ->and($contractor->fresh()->profession_id)->toBe($profession->id);
});

it('لا ينفّذ الحذف بلا تأكيدٍ مسبق', function () {
    // ⚠️ النداء يصل في طلب مستقل — فإجراءٌ لم يُطلب تأكيده لا يُنفَّذ
    $this->actingAs(professionUser(['contractors.settings']));

    $profession = Profession::create(['name' => 'فني شبكات', 'order' => 9]);

    Livewire::test(Index::class)
        ->set('deletingId', $profession->id)
        ->call('deleteRow');

    expect(Profession::find($profession->id))->not->toBeNull();
});

// ── الظهور في الفورم ─────────────────────────────────────

it('يُخفي الصفة المعطَّلة عن الإضافة ويُبقيها على أصحابها', function () {
    $governorate = Governorate::factory()->create();
    $office      = Office::factory()->create(['governorate_id' => $governorate->id]);

    $disabled   = Profession::create(['name' => 'فني شبكات', 'order' => 9, 'is_active' => false]);
    $contractor = Contractor::factory()->create(['profession_id' => $disabled->id]);
    ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => '2026-09-01',
    ]);

    $user = professionUser(['contractors.index', 'contractors.create', 'contractors.edit']);
    $user->governorates()->sync([$governorate->id]);
    $this->actingAs($user->fresh());

    // الإضافة: المعطَّلة غائبة
    expect(Livewire::test(App\Livewire\Contractors\Create::class)
        ->viewData('professions')->pluck('name')->all())->not->toContain('فني شبكات');

    // والتعديل: حاضرة لصاحبها وحده، وإلا بدا الحقل فارغاً وغيّرت الصفةَ حفظةٌ عابرة
    expect(Livewire::test(App\Livewire\Contractors\Create::class, ['contractor' => $contractor])
        ->viewData('professions')->pluck('name')->all())->toContain('فني شبكات');
});
