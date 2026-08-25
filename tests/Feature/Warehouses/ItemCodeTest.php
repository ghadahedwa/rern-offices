<?php

use App\Livewire\Warehouses\Items\Create as ItemCreate;
use App\Livewire\Warehouses\Items\Index as ItemIndex;
use App\Models\Item;
use App\Models\ItemCategory;

/** الدفتر العقاري وفهرس التوثيق هما القسمان المرصودان بأرقام أصناف. */
function registryCategory(): ItemCategory
{
    return ItemCategory::firstOrCreate(['name' => 'الدفتر العقاري (١)'], ['order' => 6]);
}

// ── سؤال «هل للصنف رقم؟» ─────────────────────────────────

it('يحفظ الصنف بلا رقم حين لا يُشيَّك السؤال', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'حبر توشيبا')
        ->set('item_category_id', registryCategory()->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', false)
        ->call('save');

    expect(Item::where('name', 'حبر توشيبا')->first()->code)->toBeNull();
});

it('يشترط الرقم حين يُشيَّك السؤال', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'شطب كلى')
        ->set('item_category_id', registryCategory()->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '')
        ->call('save')
        ->assertHasErrors(['code' => 'required']);

    expect(Item::where('name', 'شطب كلى')->exists())->toBeFalse();
});

it('يمسح الرقم عند رفع علامة السؤال', function () {
    // وإلا بقي رقم في الحقل مخفياً ثم حُفظ مع صنف أُعلن أنه بلا رقم
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('has_code', true)
        ->set('code', '٤٠ ق')
        ->set('has_code', false)
        ->assertSet('code', '');
});

it('لا يحفظ رقماً لصنف رُفعت عنه علامة السؤال', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'صنف بلا رقم')
        ->set('item_category_id', registryCategory()->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '٤٠ ق')
        ->set('has_code', false)
        ->call('save');

    expect(Item::where('name', 'صنف بلا رقم')->first()->code)->toBeNull();
});

it('يعبّئ السؤال من رقم الصنف عند التعديل', function () {
    // الخانة مشتقّة من العمود لا مخزَّنة — فلا بد أن تُستنتج عند فتح الشاشة
    $withCode = Item::create([
        'name' => 'شطب جزئى', 'item_category_id' => registryCategory()->id,
        'code' => '٤١ ق', 'item_unit_id' => anyUnit()->id,
    ]);
    $withoutCode = Item::create([
        'name' => 'حبر كانون', 'item_category_id' => registryCategory()->id,
        'item_unit_id' => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class, ['item' => $withCode])
        ->assertSet('has_code', true)
        ->assertSet('code', '٤١ ق');

    Livewire::test(ItemCreate::class, ['item' => $withoutCode])
        ->assertSet('has_code', false)
        ->assertSet('code', '');
});

// ── توحيد صورة الأرقام ───────────────────────────────────

it('يخزّن الرقم بأرقام هندية وإن كُتب لاتينياً', function () {
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'تأشير هامشى')
        ->set('item_category_id', registryCategory()->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '54 ق م')
        ->call('save');

    expect(Item::where('name', 'تأشير هامشى')->first()->code)->toBe('٥٤ ق م');
});

it('يحفظ اللاحقة ولا يعاملها عدداً', function () {
    // «٥٤ ق» و«٥٤ ق م» صنفان مختلفان — أي تخزين عددي يبتلع اللاحقة
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'حركة التأشير')
        ->set('item_category_id', registryCategory()->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '٥٤ ق م')
        ->call('save');

    expect(Item::where('name', 'حركة التأشير')->first()->code)->toBe('٥٤ ق م');
});

// ── تحذير التكرار (لا منع) ───────────────────────────────

it('يحذّر من رقم مكرر في القسم نفسه ولا يحفظ من أول ضغطة', function () {
    $category = registryCategory();
    Item::create([
        'name' => 'شطب كلى', 'item_category_id' => $category->id,
        'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    $screen = Livewire::test(ItemCreate::class)
        ->set('name', 'صنف آخر')
        ->set('item_category_id', $category->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '٤٠ ق')
        ->call('save');

    expect($screen->get('duplicateWarning'))->toContain('شطب كلى')
        ->and(Item::where('name', 'صنف آخر')->exists())->toBeFalse();
});

it('يحفظ الرقم المكرر عند الضغط مرة ثانية', function () {
    // تحذير لا منع: رفضُ رقم موجود فعلاً في الدفتر يدفع الموظف لمخالفة الورق
    $category = registryCategory();
    Item::create([
        'name' => 'شطب كلى', 'item_category_id' => $category->id,
        'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'صنف آخر')
        ->set('item_category_id', $category->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '٤٠ ق')
        ->call('save')
        ->call('save');

    expect(Item::where('name', 'صنف آخر')->first()->code)->toBe('٤٠ ق');
});

it('يكشف التكرار ولو كُتب الرقم لاتينياً', function () {
    // بلا توحيد صورة الأرقام يمرّ «40 ق» بجوار «٤٠ ق» بلا تحذير — وهو أهم ما وُضع له الفحص
    $category = registryCategory();
    Item::create([
        'name' => 'شطب كلى', 'item_category_id' => $category->id,
        'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    $screen = Livewire::test(ItemCreate::class)
        ->set('name', 'صنف آخر')
        ->set('item_category_id', $category->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '40 ق')
        ->call('save');

    expect($screen->get('duplicateWarning'))->toContain('شطب كلى');
});

it('يُبطل التأكيد إذا غُيّر الرقم بعد التحذير', function () {
    // التأكيد مربوط ببصمة «قسم|رقم» — وإلا مرّ رقم مكرر آخر بلا تحذير
    $category = registryCategory();
    Item::create(['name' => 'شطب كلى',  'item_category_id' => $category->id, 'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'شطب جزئى', 'item_category_id' => $category->id, 'code' => '٤١ ق', 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    $screen = Livewire::test(ItemCreate::class)
        ->set('name', 'صنف آخر')
        ->set('item_category_id', $category->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '٤٠ ق')
        ->call('save')            // تحذير على ٤٠ ق
        ->set('code', '٤١ ق')
        ->call('save');           // رقم جديد مكرر: يجب أن يحذّر من جديد

    expect($screen->get('duplicateWarning'))->toContain('شطب جزئى')
        ->and(Item::where('name', 'صنف آخر')->exists())->toBeFalse();
});

it('لا يحذّر من الرقم نفسه في قسم آخر', function () {
    $registry = registryCategory();
    $photo    = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 3]);
    Item::create([
        'name' => 'شطب كلى', 'item_category_id' => $registry->id,
        'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class)
        ->set('name', 'صنف تصوير')
        ->set('item_category_id', $photo->id)
        ->set('item_unit_id', anyUnit()->id)
        ->set('has_code', true)
        ->set('code', '٤٠ ق')
        ->call('save');

    expect(Item::where('name', 'صنف تصوير')->first()->code)->toBe('٤٠ ق');
});

it('لا يحذّر الصنف من رقم نفسه عند تعديله', function () {
    $item = Item::create([
        'name' => 'شطب كلى', 'item_category_id' => registryCategory()->id,
        'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id,
    ]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemCreate::class, ['item' => $item])
        ->set('name', 'شطب كلى — معدَّل')
        ->call('save');

    expect($item->fresh()->name)->toBe('شطب كلى — معدَّل')
        ->and($item->fresh()->code)->toBe('٤٠ ق');
});

// ── البحث بالرقم ─────────────────────────────────────────

it('يجد الصنف برقمه في شاشة الأصناف', function () {
    $category = registryCategory();
    Item::create(['name' => 'شطب كلى',  'item_category_id' => $category->id, 'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'شطب جزئى', 'item_category_id' => $category->id, 'code' => '٤١ ق', 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('search', '٤٠ ق')
        ->assertSee('شطب كلى')
        ->assertDontSee('شطب جزئى');
});

it('يجد الصنف برقمه ولو كُتب البحث لاتينياً', function () {
    $category = registryCategory();
    Item::create(['name' => 'شطب كلى',  'item_category_id' => $category->id, 'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'شطب جزئى', 'item_category_id' => $category->id, 'code' => '٤١ ق', 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('search', '40')
        ->assertSee('شطب كلى')
        ->assertDontSee('شطب جزئى');
});

it('يبقي البحث بالاسم عاملاً بعد إضافة البحث بالرقم', function () {
    $category = registryCategory();
    Item::create(['name' => 'شطب كلى',  'item_category_id' => $category->id, 'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'حبر توشيبا', 'item_category_id' => $category->id, 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('search', 'توشيبا')
        ->assertSee('حبر توشيبا')
        ->assertDontSee('شطب كلى');
});

it('يجمع البحث بالرقم مع فلتر القسم ولا يتخطاه', function () {
    // البحث يُضاف بـOR داخلياً — لو لم يُلفّ بمجموعته لابتلع فلتر القسم
    $registry = registryCategory();
    $photo    = ItemCategory::create(['name' => 'مخزن التصوير', 'order' => 3]);
    Item::create(['name' => 'شطب كلى',   'item_category_id' => $registry->id, 'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id]);
    Item::create(['name' => 'صنف تصوير', 'item_category_id' => $photo->id,    'code' => '٤٠ ق', 'item_unit_id' => anyUnit()->id]);
    $this->actingAs(categoriesManager());

    Livewire::test(ItemIndex::class)
        ->set('categoryFilter', (string) $photo->id)
        ->set('search', '٤٠ ق')
        ->assertSee('صنف تصوير')
        ->assertDontSee('شطب كلى');
});
