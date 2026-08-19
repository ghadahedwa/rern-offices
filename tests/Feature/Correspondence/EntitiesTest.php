<?php

use App\Livewire\Correspondence\Entities\Create;
use App\Livewire\Correspondence\Entities\Index;
use App\Models\CorrespondenceEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/** مستخدم يملك صلاحية إعدادات المراسلات وحدها — لا سوبر أدمن. */
function entitiesManager(): User
{
    Permission::findOrCreate('correspondence.settings', 'web');
    $role = Role::findOrCreate('corr-settings-manager', 'web');
    $role->givePermissionTo('correspondence.settings');

    return tap(User::factory()->create())->assignRole($role);
}

/** مستخدم بلا أي صلاحية. */
function entitiesOutsider(): User
{
    Permission::findOrCreate('correspondence.settings', 'web');

    return User::factory()->create();
}

// ── الحراسة ──────────────────────────────────────────────

it('يمنع من لا يملك صلاحية إعدادات المراسلات من شاشة الأطراف', function () {
    $this->actingAs(entitiesOutsider());

    Livewire::test(Index::class)->assertForbidden();
});

it('يمنع من لا يملك الصلاحية من شاشة إضافة طرف', function () {
    $this->actingAs(entitiesOutsider());

    Livewire::test(Create::class)->assertForbidden();
});

it('يسمح لصاحب الصلاحية بفتح شاشة الأطراف', function () {
    $this->actingAs(entitiesManager());

    Livewire::test(Index::class)->assertOk();
});

it('يمنع الحذف بعد سحب الصلاحية والشاشة مفتوحة', function () {
    // الحذف يصل في طلب مستقل عن mount — فالحارس داخل الإجراء نفسه لا في mount وحده
    $entity  = CorrespondenceEntity::create(['name' => 'إدارة التفتيش', 'code' => 'تفتيش', 'order' => 3]);
    $manager = entitiesManager();
    $this->actingAs($manager);

    $screen = Livewire::test(Index::class)->assertOk();

    $manager->removeRole('corr-settings-manager');
    $manager->forgetCachedPermissions();

    $screen->call('askDelete', $entity->id)->assertForbidden();

    expect(CorrespondenceEntity::find($entity->id))->not->toBeNull();
});

// ── الجدول والبيانات المزروعة ────────────────────────────

it('يزرع الطرفين المؤكَّدين برمزيهما', function () {
    $entities = CorrespondenceEntity::orderBy('order')->pluck('code', 'name')->all();

    expect($entities)->toBe([
        'رئاسة المصلحة' => 'رئاسة',
        'المكتب الفني'  => 'فني',
    ]);
});

it('يرفض تكرار الرمز — لأن دوره منع الخلط بين الدفاتر', function () {
    $this->actingAs(entitiesManager());

    Livewire::test(Create::class)
        ->set('name', 'طرف آخر')
        ->set('code', 'رئاسة')   // رمز مستخدم بالفعل
        ->set('order', 5)
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

it('يرفض تكرار اسم الطرف', function () {
    $this->actingAs(entitiesManager());

    Livewire::test(Create::class)
        ->set('name', 'المكتب الفني')
        ->set('code', 'مكتب')
        ->set('order', 5)
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('يسمح للطرف بالاحتفاظ برمزه عند التعديل', function () {
    $this->actingAs(entitiesManager());
    $entity = CorrespondenceEntity::where('code', 'فني')->first();

    Livewire::test(Create::class, ['entity' => $entity])
        ->set('name', 'المكتب الفني للقطاع')
        ->call('save')
        ->assertHasNoErrors();

    expect($entity->fresh()->name)->toBe('المكتب الفني للقطاع')
        ->and($entity->fresh()->code)->toBe('فني');
});

// ── الـCRUD ──────────────────────────────────────────────

it('يضيف طرفاً جديداً فيأخذ دفتره الخاص بلا كود إضافي', function () {
    $this->actingAs(entitiesManager());

    Livewire::test(Create::class)
        ->set('name', 'إدارة التفتيش')
        ->set('code', 'تفتيش')
        ->set('order', 3)
        ->call('save')
        ->assertHasNoErrors();

    $entity = CorrespondenceEntity::where('code', 'تفتيش')->first();

    expect($entity)->not->toBeNull()
        ->and($entity->name)->toBe('إدارة التفتيش')
        ->and($entity->is_active)->toBeTrue();
});

it('يعطي الطرف الجديد آخر الطابور لا أوّله', function () {
    // بلا هذا يبدأ الترتيب من صفر فيقفز الطرف الجديد قبل رئاسة المصلحة
    $this->actingAs(entitiesManager());

    Livewire::test(Create::class)->assertSet('order', 3);   // المزروعان ١ و٢

    Livewire::test(Create::class)
        ->set('name', 'إدارة التفتيش')
        ->set('code', 'تفتيش')
        ->call('save')
        ->assertHasNoErrors();

    expect(CorrespondenceEntity::orderBy('order')->pluck('name')->all())
        ->toBe(['رئاسة المصلحة', 'المكتب الفني', 'إدارة التفتيش']);
});

it('يحذف الطرف عبر المودال المشترك', function () {
    $this->actingAs(entitiesManager());
    $entity = CorrespondenceEntity::create(['name' => 'طرف مؤقت', 'code' => 'مؤقت', 'order' => 9]);

    Livewire::test(Index::class)
        ->call('askDelete', $entity->id)
        ->assertSet('showDelete', true)
        ->assertSet('deletingLabel', 'طرف مؤقت')
        ->call('deleteRow');

    expect(CorrespondenceEntity::find($entity->id))->toBeNull();
});

it('يتجاهل الحذف إذا كان المودال مغلقاً — لا إجراء مؤجَّل بطلب مستقل', function () {
    $this->actingAs(entitiesManager());
    $entity = CorrespondenceEntity::create(['name' => 'طرف محفوظ', 'code' => 'محفوظ', 'order' => 9]);

    Livewire::test(Index::class)
        ->set('deletingId', $entity->id)
        ->set('showDelete', false)
        ->call('deleteRow');

    expect(CorrespondenceEntity::find($entity->id))->not->toBeNull();
});

// ── أعمدة العرض ──────────────────────────────────────────

it('يعرض عدد مستخدمي كل طرف', function () {
    $this->actingAs(entitiesManager());

    $presidency = CorrespondenceEntity::where('code', 'رئاسة')->first();
    User::factory()->count(2)->create(['correspondence_entity_id' => $presidency->id]);
    User::factory()->create();   // بلا طرف — لا يُحتسب على أحد

    $rows = Livewire::test(Index::class)->viewData('entities');

    expect($rows->firstWhere('code', 'رئاسة')->users_count)->toBe(2)
        ->and($rows->firstWhere('code', 'فني')->users_count)->toBe(0);
});

it('يعرض حالة الطرف في عمود مستقل', function () {
    $this->actingAs(entitiesManager());
    CorrespondenceEntity::create(['name' => 'طرف موقوف', 'code' => 'وقف', 'order' => 7, 'is_active' => false]);

    Livewire::test(Index::class)
        ->assertSee('مُفعَّل')
        ->assertSee('موقوف');
});

// ── البحث والفلترة ───────────────────────────────────────

it('يبحث بالعربية المطبَّعة — التاء المربوطة والمسافات', function () {
    // «رئاسه المصلحه» بالهاء وبمسافة ← تجد «رئاسة المصلحة» بالتاء المربوطة.
    // ⚠️ ArabicText يوحّد (أ إ آ ٱ→ا · ى→ي · ة→ه) ولا يوحّد الهمزة على نبرة (ئ).
    $this->actingAs(entitiesManager());

    Livewire::test(Index::class)
        ->set('search', 'رئاسه المصلحه')
        ->assertSee('رئاسة المصلحة')
        ->assertDontSee('المكتب الفني');
});

it('يفلتر بالحالة فيُخفي الطرف الموقوف', function () {
    $this->actingAs(entitiesManager());
    CorrespondenceEntity::create(['name' => 'طرف موقوف', 'code' => 'موقوف', 'order' => 8, 'is_active' => false]);

    Livewire::test(Index::class)
        ->set('activeFilter', 'yes')
        ->assertSee('رئاسة المصلحة')
        ->assertDontSee('طرف موقوف')
        ->set('activeFilter', 'no')
        ->assertSee('طرف موقوف')
        ->assertDontSee('رئاسة المصلحة');
});
