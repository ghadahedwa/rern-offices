<?php

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use Illuminate\Support\Facades\DB;

it('يمسح الوحدات ويعيد العدّاد إلى ١ حين لا صنف مرتبطاً بها', function () {
    ItemUnit::query()->delete();
    ItemUnit::create(['name' => 'قطعة']);
    ItemUnit::create(['name' => 'جهاز']);
    ItemUnit::create(['name' => 'دفتر']);

    $this->artisan('warehouses:reset-item-units')->assertSuccessful();

    expect(ItemUnit::count())->toBe(0);

    // العدّاد عاد فعلاً: أول صفٍّ جديد يأخذ المعرّف ١
    expect(ItemUnit::create(['name' => 'قطعة'])->id)->toBe(1);
});

it('يرفض المسح ما دام صنفٌ مرتبطاً بوحدة', function () {
    // الـFK بـnullOnDelete: بلا هذا الحارس يمرّ الحذف صامتاً ويُفرّغ وحدة كل صنف
    $unit     = ItemUnit::firstOrCreate(['name' => 'قطعة']);
    $category = ItemCategory::firstOrCreate(['name' => 'مخزن التصوير'], ['order' => 1]);
    $item     = Item::create([
        'name'             => 'حبر توشيبا',
        'item_category_id' => $category->id,
        'item_unit_id'     => $unit->id,
    ]);

    $this->artisan('warehouses:reset-item-units')->assertFailed();

    expect(ItemUnit::find($unit->id))->not->toBeNull()
        ->and($item->fresh()->item_unit_id)->toBe($unit->id);
});

it('ينفّذ المسح رغم الارتباط عند تمرير force', function () {
    $unit     = ItemUnit::firstOrCreate(['name' => 'قطعة']);
    $category = ItemCategory::firstOrCreate(['name' => 'مخزن التصوير'], ['order' => 1]);
    $item     = Item::create([
        'name'             => 'حبر توشيبا',
        'item_category_id' => $category->id,
        'item_unit_id'     => $unit->id,
    ]);

    $this->artisan('warehouses:reset-item-units', ['--force' => true])->assertSuccessful();

    expect(ItemUnit::count())->toBe(0)
        // وهذا بالضبط ما يحرسه الرفض أعلاه: الصنف يفقد وحدته بلا خطأ
        ->and($item->fresh()->item_unit_id)->toBeNull();
});

it('يعيد الوحدات بترتيب نظيف حين يُشغَّل قبل زرع الأصناف', function () {
    ItemUnit::query()->delete();
    ItemUnit::create(['name' => 'وحدة قديمة']);

    $this->artisan('warehouses:reset-item-units')->assertSuccessful();
    $this->artisan('db:seed', ['--class' => 'WarehouseItemsFromStatementsSeeder'])->assertSuccessful();

    expect(ItemUnit::orderBy('id')->pluck('name')->all())->toBe([
        'قطعة', 'جهاز', 'دفتر', 'رزمة', 'عبوة', 'نموذج/مستند', 'حافظة', 'عقد', 'متر',
    ])
        ->and(ItemUnit::orderBy('id')->first()->id)->toBe(1)
        ->and(Item::whereNull('item_unit_id')->count())->toBe(0);
});

it('يعمل على قاعدة بلا وحدات أصلاً', function () {
    ItemUnit::query()->delete();

    $this->artisan('warehouses:reset-item-units')->assertSuccessful();

    expect(ItemUnit::count())->toBe(0);
});

it('لا يمسّ جداول أخرى', function () {
    ItemUnit::firstOrCreate(['name' => 'قطعة']);
    $before = DB::table('item_categories')->count();

    $this->artisan('warehouses:reset-item-units')->assertSuccessful();

    expect(DB::table('item_categories')->count())->toBe($before);
});
