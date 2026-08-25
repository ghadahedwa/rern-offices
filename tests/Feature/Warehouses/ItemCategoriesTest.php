<?php

use App\Livewire\Warehouses\Categories\Create as CategoryCreate;
use App\Livewire\Warehouses\Categories\Index as CategoryIndex;
use App\Livewire\Warehouses\Items\Create as ItemCreate;
use App\Livewire\Warehouses\Items\Index as ItemIndex;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** مستخدم يملك صلاحية إعدادات المخازن وحدها — لا سوبر أدمن. */
function categoriesManager(): User
{
    Permission::findOrCreate('warehouses.settings', 'web');
    $role = Role::findOrCreate('wh-settings-manager', 'web');
    $role->givePermissionTo('warehouses.settings');

    return tap(User::factory()->create())->assignRole($role);
}

/** مستخدم بلا أي صلاحية. */
function categoriesOutsider(): User
{
    Permission::findOrCreate('warehouses.settings', 'web');

    return User::factory()->create();
}

function anyUnit(): ItemUnit
{
    return ItemUnit::firstOrCreate(['name' => 'قطعة']);
}

/** قسم واحد تُعلَّق عليه أصناف اختبارات الفلاتر. */
function registryCategoryForFilters(): ItemCategory
{
    return ItemCategory::firstOrCreate(['name' => 'مخزن التصوير'], ['order' => 3]);
}

// ── الحراسة ──────────────────────────────────────────────

it('يمنع من لا يملك صلاحية إعدادات المخازن من شاشة الأقسام', function () {
    $this->actingAs(categoriesOutsider());

    Livewire::test(CategoryIndex::class)->assertForbidden();
});

it('يمنع من لا يملك الصلاحية من شاشة إضافة قسم', function () {
    $this->actingAs(categoriesOutsider());

    Livewire::test(CategoryCreate::class)->assertForbidden();
});

it('يسمح لصاحب الصلاحية بفتح شاشة الأقسام', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryIndex::class)->assertOk();
});

it('يمنع فتح مودال الحذف بعد سحب الصلاحية والشاشة مفتوحة', function () {
    // الحذف يصل في طلب مستقل عن mount — فالحارس داخل الإجراء نفسه لا في mount وحده
    $category = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 3]);
    $manager  = categoriesManager();
    $this->actingAs($manager);

    $screen = Livewire::test(CategoryIndex::class)->assertOk();

    $manager->removeRole('wh-settings-manager');
    $manager->forgetCachedPermissions();

    $screen->call('askDelete', $category->id)->assertForbidden();

    expect(ItemCategory::find($category->id))->not->toBeNull();
});

it('يمنع تنفيذ الحذف بعد سحب الصلاحية والمودال مفتوح', function () {
    // المودال يُفتح بالصلاحية ثم تُسحب قبل التأكيد — والتأكيد طلب ثالث مستقل
    $category = ItemCategory::create(['name' => 'قسم فارغ', 'order' => 3]);
    $manager  = categoriesManager();
    $this->actingAs($manager);

    $screen = Livewire::test(CategoryIndex::class)
        ->call('askDelete', $category->id)
        ->assertSet('showDelete', true);

    $manager->removeRole('wh-settings-manager');
    $manager->forgetCachedPermissions();

    $screen->call('deleteRow')->assertForbidden();

    expect(ItemCategory::find($category->id))->not->toBeNull();
});

// ── الأقسام المزروعة ─────────────────────────────────────

it('يزرع الأقسام الستة عشر بترتيب صفحات الملف الورقي', function () {
    // الترتيب هو ترتيب الدستة الورقية كما يتسلّمها أمين المخزن، وهو ترتيب
    // العرض في القوائم والتقارير — فيُثبَّت هنا ولا يُترك للصدفة
    expect(ItemCategory::count())->toBe(16)
        ->and(ItemCategory::orderBy('order')->pluck('name')->all())->toBe([
            'مخزن التصوير',
            'مخزن المستديم',
            'مخزن السيارات',
            'مخزن المستهلك',
            'مخزن ذات القيمة',
            'نماذج قانون ٩ و٢٧',
            'أظرف قانون ٩ لسنة ٢٠٢٢',
            'أظرف قانون ٢٧ لسنة ٢٠١٨',
            'فهرس التوثيق',
            'الدفتر العقاري (١)',
            'مخزن السجل العيني',
            'مخزن الكمبيوتر',
            'الورق والحافظات والنماذج المؤمنة والعقود المتموغة',
            'الأرصدة الكتابية',
            'الأختام',
            'الأرصدة الحسابية',
        ])
        ->and(ItemCategory::pluck('order')->all())->toBe(range(1, 16));
});

// ── إدارة الأقسام ────────────────────────────────────────

it('ينشئ قسماً جديداً من الشاشة', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryCreate::class)
        ->set('name', 'مخزن الأثاث')
        ->set('order', 9)
        ->call('save');

    expect(ItemCategory::where('name', 'مخزن الأثاث')->first())
        ->not->toBeNull()
        ->order->toBe(9)
        ->is_active->toBeTrue();
});

it('يرفض قسماً بلا اسم', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryCreate::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('يعدّل قسماً قائماً', function () {
    $category = ItemCategory::create(['name' => 'قسم قديم', 'order' => 2]);
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryCreate::class, ['itemCategory' => $category])
        ->assertSet('name', 'قسم قديم')
        ->set('name', 'قسم جديد')
        ->call('save');

    expect($category->fresh()->name)->toBe('قسم جديد');
});

it('يقترح ترتيباً تالياً لآخر قسم عند الإضافة', function () {
    ItemCategory::query()->delete();
    ItemCategory::create(['name' => 'أ', 'order' => 4]);
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryCreate::class)->assertSet('order', 5);
});

it('يبحث في أسماء الأقسام بالتطبيع العربي', function () {
    ItemCategory::query()->delete();
    ItemCategory::create(['name' => 'مخزن ذات القيمة', 'order' => 1]);
    ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 2]);
    $this->actingAs(categoriesManager());

    // «القيمه» بالهاء تطابق «القيمة» بالتاء المربوطة
    Livewire::test(CategoryIndex::class)
        ->set('search', 'القيمه')
        ->assertSee('مخزن ذات القيمة')
        ->assertDontSee('مخزن التصوير');
});

// ── حارس الحذف ───────────────────────────────────────────

it('يمنع حذف قسم مستعمل ويبقي أصنافه على قسمها', function () {
    // الـFK بـnullOnDelete: بلا هذا الحارس يمرّ الحذف صامتاً ويترك الأصناف بلا قسم
    $category = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    $item = Item::create([
        'name'             => 'حبر توشيبا',
        'item_category_id' => $category->id,
        'item_unit_id'     => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryIndex::class)
        ->call('askDelete', $category->id)
        ->assertSet('deletingWarning', __('home.item_category_in_use_warning', ['count' => 1]))
        ->call('deleteRow');

    expect(ItemCategory::find($category->id))->not->toBeNull()
        ->and($item->fresh()->item_category_id)->toBe($category->id);
});

it('يحذف قسماً لا أصناف له', function () {
    $category = ItemCategory::create(['name' => 'قسم فارغ', 'order' => 1]);
    $this->actingAs(categoriesManager());

    Livewire::test(CategoryIndex::class)
        ->call('askDelete', $category->id)
        ->assertSet('deletingWarning', '')
        ->call('deleteRow');

    expect(ItemCategory::find($category->id))->toBeNull();
});

// ── القسم على الصنف ──────────────────────────────────────

it('يشترط قسماً لكل صنف جديد', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'حبر كانون')
        ->set('item_unit_id', anyUnit()->id)
        ->set('item_category_id', null)
        ->call('save')
        ->assertHasErrors(['item_category_id' => 'required']);

    expect(Item::where('name', 'حبر كانون')->exists())->toBeFalse();
});

it('يرفض قسماً غير موجود يصل من العميل', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'صنف ملفق')
        ->set('item_unit_id', anyUnit()->id)
        ->set('item_category_id', 999999)
        ->call('save')
        ->assertHasErrors(['item_category_id']);
});

it('يحفظ الصنف بقسمه وترتيبه', function () {
    $category = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'درام زيروكس')
        ->set('item_unit_id', anyUnit()->id)
        ->set('item_category_id', $category->id)
        ->set('order', 4)
        ->call('save');

    expect(Item::where('name', 'درام زيروكس')->first())
        ->item_category_id->toBe($category->id)
        ->order->toBe(4);
});

it('يصفر الترتيب المتروك فارغاً بدل حفظه null', function () {
    // العمود NOT NULL بافتراضي صفر — تمرير null يُسقط الحفظ
    $category = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'حبر زيروكس')
        ->set('item_unit_id', anyUnit()->id)
        ->set('item_category_id', $category->id)
        ->set('order', null)
        ->call('save');

    expect(Item::where('name', 'حبر زيروكس')->first()->order)->toBe(0);
});

it('لا يعرض القسم المتوقف لصنف جديد', function () {
    ItemCategory::query()->delete();
    ItemCategory::create(['name' => 'قسم عامل', 'order' => 1, 'is_active' => true]);
    ItemCategory::create(['name' => 'قسم متوقف', 'order' => 2, 'is_active' => false]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->assertSee('قسم عامل')
        ->assertDontSee('قسم متوقف');
});

it('يبقي القسم المتوقف معروضاً على الصنف المرتبط به', function () {
    // وإلا أفرغ الـselect حقل الصنف صامتاً عند أول حفظ لسبب آخر
    ItemCategory::query()->delete();
    $stopped = ItemCategory::create(['name' => 'قسم متوقف', 'order' => 2, 'is_active' => false]);
    $item = Item::create([
        'name'             => 'صنف قديم',
        'item_category_id' => $stopped->id,
        'item_unit_id'     => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class, ['item' => $item])
        ->assertSee('قسم متوقف')
        ->assertSet('item_category_id', $stopped->id);
});

// ── فلتر شاشة الأصناف ────────────────────────────────────

it('يفلتر الأصناف بالقسم', function () {
    $photo = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    $fixed = ItemCategory::create(['name' => 'مخزن المستديم', 'order' => 2]);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $photo->id, 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'بونطة شنيور', 'item_category_id' => $fixed->id, 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('categoryFilter', (string) $photo->id)
        ->assertSee('حبر توشيبا')
        ->assertDontSee('بونطة شنيور');
});

it('يعرض الأصناف بلا قسم عند اختيار «بلا قسم»', function () {
    $photo = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $photo->id, 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'صنف قديم بلا تصنيف', 'item_category_id' => null, 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('categoryFilter', 'none')
        ->assertSee('صنف قديم بلا تصنيف')
        ->assertDontSee('حبر توشيبا');
});

it('يتجاهل قيمة فلتر تالفة تصل من الرابط', function () {
    // الفلتر مربوط بالـURL، فقيمة غير رقمية وغير none لا يجوز أن تُسقط الصفحة ولا أن تُفلتر
    $photo = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $photo->id, 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'صنف بلا تصنيف', 'item_category_id' => null, 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::withQueryParams(['category' => '1) or 1=1--'])
        ->test(ItemIndex::class)
        ->assertOk()
        ->assertSee('حبر توشيبا')
        ->assertSee('صنف بلا تصنيف');
});

// ── فلتر الوحدة ──────────────────────────────────────────

it('يفلتر الأصناف بالوحدة', function () {
    $category = registryCategoryForFilters();
    $piece    = ItemUnit::firstOrCreate(['name' => 'قطعة']);
    $device   = ItemUnit::firstOrCreate(['name' => 'جهاز']);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $category->id, 'item_unit_id' => $piece->id]);
    Item::create(['name' => 'ماكينة تصوير', 'item_category_id' => $category->id, 'item_unit_id' => $device->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('unitFilter', (string) $device->id)
        ->assertSee('ماكينة تصوير')
        ->assertDontSee('حبر توشيبا');
});

it('يعرض الأصناف بلا وحدة عند اختيار «بلا وحدة»', function () {
    $category = registryCategoryForFilters();
    $piece    = ItemUnit::firstOrCreate(['name' => 'قطعة']);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $category->id, 'item_unit_id' => $piece->id]);
    Item::create(['name' => 'صنف بلا وحدة', 'item_category_id' => $category->id, 'item_unit_id' => null]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('unitFilter', 'none')
        ->assertSee('صنف بلا وحدة')
        ->assertDontSee('حبر توشيبا');
});

it('يتجاهل قيمة وحدة تالفة تصل من الرابط', function () {
    $category = registryCategoryForFilters();
    $piece    = ItemUnit::firstOrCreate(['name' => 'قطعة']);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $category->id, 'item_unit_id' => $piece->id]);
    Item::create(['name' => 'صنف بلا وحدة', 'item_category_id' => $category->id, 'item_unit_id' => null]);
    $this->actingAs(categoriesManager());

    Livewire::withQueryParams(['unit' => 'قطعة'])
        ->test(ItemIndex::class)
        ->assertOk()
        ->assertSee('حبر توشيبا')
        ->assertSee('صنف بلا وحدة');
});

it('يجمع فلتر الوحدة مع القسم والحالة والبحث معاً', function () {
    // البحث يُضاف بـOR داخلياً — لو لم يُلفّ بمجموعته لابتلع بقية الفلاتر
    $photo  = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    $fixed  = ItemCategory::create(['name' => 'مخزن المستديم', 'order' => 2]);
    $device = ItemUnit::firstOrCreate(['name' => 'جهاز']);
    $piece  = ItemUnit::firstOrCreate(['name' => 'قطعة']);

    Item::create(['name' => 'حبر توشيبا مطلوب', 'item_category_id' => $photo->id, 'item_unit_id' => $device->id, 'is_active' => true]);
    Item::create(['name' => 'حبر توشيبا متوقف', 'item_category_id' => $photo->id, 'item_unit_id' => $device->id, 'is_active' => false]);
    Item::create(['name' => 'حبر توشيبا قطعة',  'item_category_id' => $photo->id, 'item_unit_id' => $piece->id,  'is_active' => true]);
    Item::create(['name' => 'حبر توشيبا مستديم', 'item_category_id' => $fixed->id, 'item_unit_id' => $device->id, 'is_active' => true]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('categoryFilter', (string) $photo->id)
        ->set('unitFilter', (string) $device->id)
        ->set('statusFilter', 'yes')
        ->set('search', 'توشيبا')
        ->assertSee('حبر توشيبا مطلوب')
        ->assertDontSee('حبر توشيبا متوقف')
        ->assertDontSee('حبر توشيبا قطعة')
        ->assertDontSee('حبر توشيبا مستديم');
});

// ── فلتر الحالة ──────────────────────────────────────────

it('يفلتر الأصناف النشطة وحدها', function () {
    $category = registryCategoryForFilters();
    Item::create(['name' => 'صنف نشط',    'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => true]);
    Item::create(['name' => 'صنف متوقف', 'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => false]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('statusFilter', 'yes')
        ->assertSee('صنف نشط')
        ->assertDontSee('صنف متوقف');
});

it('يفلتر الأصناف غير النشطة وحدها', function () {
    $category = registryCategoryForFilters();
    Item::create(['name' => 'صنف نشط',    'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => true]);
    Item::create(['name' => 'صنف متوقف', 'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => false]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('statusFilter', 'no')
        ->assertSee('صنف متوقف')
        ->assertDontSee('صنف نشط');
});

it('يعرض الحالتين حين لا تُختار حالة', function () {
    $category = registryCategoryForFilters();
    Item::create(['name' => 'صنف نشط',    'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => true]);
    Item::create(['name' => 'صنف متوقف', 'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => false]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->assertSee('صنف نشط')
        ->assertSee('صنف متوقف');
});

it('يتجاهل قيمة حالة تالفة تصل من الرابط', function () {
    // 'yes' === القيمة تُقارن بـ — فأي قيمة أخرى كانت ستُفسَّر «غير نشط» صامتاً
    $category = registryCategoryForFilters();
    Item::create(['name' => 'صنف نشط',    'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => true]);
    Item::create(['name' => 'صنف متوقف', 'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id, 'is_active' => false]);
    $this->actingAs(categoriesManager());

    Livewire::withQueryParams(['status' => 'نشط'])
        ->test(ItemIndex::class)
        ->assertOk()
        ->assertSee('صنف نشط')
        ->assertSee('صنف متوقف');
});

it('يجمع فلتر الحالة مع فلتر القسم', function () {
    $photo = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 1]);
    $fixed = ItemCategory::create(['name' => 'مخزن المستديم', 'order' => 2]);
    Item::create(['name' => 'حبر متوقف',   'item_category_id' => $photo->id, 'item_unit_id' => anyUnit()->id, 'is_active' => false]);
    Item::create(['name' => 'حبر نشط',     'item_category_id' => $photo->id, 'item_unit_id' => anyUnit()->id, 'is_active' => true]);
    Item::create(['name' => 'بونطة متوقفة', 'item_category_id' => $fixed->id, 'item_unit_id' => anyUnit()->id, 'is_active' => false]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('categoryFilter', (string) $photo->id)
        ->set('statusFilter', 'no')
        ->assertSee('حبر متوقف')
        ->assertDontSee('حبر نشط')
        ->assertDontSee('بونطة متوقفة');
});

// ── الترتيب ──────────────────────────────────────────────

it('يرتب الأصناف بترتيب القسم ثم ترتيب الصنف داخله', function () {
    // الترتيب الأبجدي وحده يزيح سطور البيان المطبوع مع كل صنف جديد
    ItemCategory::query()->delete();
    $first  = ItemCategory::create(['name' => 'ي قسم أول', 'order' => 1]);
    $second = ItemCategory::create(['name' => 'أ قسم ثان', 'order' => 2]);
    Item::create(['name' => 'ي صنف', 'item_category_id' => $first->id,  'item_unit_id' => anyUnit()->id, 'order' => 1]);
    Item::create(['name' => 'أ صنف', 'item_category_id' => $first->id,  'item_unit_id' => anyUnit()->id, 'order' => 2]);
    Item::create(['name' => 'ب صنف', 'item_category_id' => $second->id, 'item_unit_id' => anyUnit()->id, 'order' => 1]);
    $this->actingAs(categoriesManager());

    $rows = Livewire::test(ItemIndex::class)->viewData('items')->pluck('name')->all();

    expect($rows)->toBe(['ي صنف', 'أ صنف', 'ب صنف']);
});

it('يضع الأصناف بلا قسم في آخر القائمة', function () {
    ItemCategory::query()->delete();
    $category = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 5]);
    Item::create(['name' => 'صنف بلا تصنيف', 'item_category_id' => null, 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    $rows = Livewire::test(ItemIndex::class)->viewData('items')->pluck('name')->all();

    expect($rows)->toBe(['حبر توشيبا', 'صنف بلا تصنيف']);
});
