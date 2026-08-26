<?php

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;

function seedCatalogue(): Warehouse
{
    // الأصناف والأقسام يزرعها seeder الأصناف، والأرصدة تحتاج مخزناً رئيسياً
    $main = WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1]);
    $wh   = Warehouse::create(['name' => 'المخزن الرئيسي بالمصلحة', 'warehouse_type_id' => $main->id]);

    Artisan::call('db:seed', ['--class' => 'WarehouseItemsFromStatementsSeeder']);

    return $wh;
}

function seedBalances(): int
{
    return Artisan::call('db:seed', ['--class' => 'WarehouseOpeningBalancesFromStatementsSeeder']);
}

it('يسجّل رصيداً افتتاحياً لكل صنف', function () {
    seedCatalogue();
    seedBalances();

    expect(WarehouseStock::count())->toBe(Item::count())
        ->and(Item::whereDoesntHave('stocks')->count())->toBe(0);
});

it('يقابل كل رصيدٍ حركةٌ افتتاحية تُثبته', function () {
    // رصيدٌ بلا حركة يجعل سجل الحركات كاذباً — رصيدٌ ظهر من العدم
    seedCatalogue();
    seedBalances();

    expect(WarehouseMovement::where('type', 'opening')->count())->toBe(WarehouseStock::count());

    $mismatched = DB::table('warehouse_stocks as s')
        ->join('warehouse_movements as m', fn ($j) => $j->on('m.item_id', 's.item_id')->on('m.warehouse_id', 's.warehouse_id'))
        ->whereColumn('m.balance_after', '!=', 's.quantity')
        ->count();

    expect($mismatched)->toBe(0);
});

it('يسجّل الأرقام على المخزن الرئيسي وحده', function () {
    $main = seedCatalogue();
    $branch = Warehouse::create([
        'name' => 'فرع للاختبار',
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'فرعي'], ['level' => 3, 'order' => 3])->id,
    ]);
    seedBalances();

    expect(WarehouseStock::where('warehouse_id', $main->id)->count())->toBeGreaterThan(0)
        ->and(WarehouseStock::where('warehouse_id', $branch->id)->count())->toBe(0);
});

it('لا يضاعف الحركات عند إعادة التشغيل', function () {
    // الافتتاحي يضبط الرصيد لكنه يُلحق حركة جديدة في كل مرة — فيبدو أن الجرد تكرّر
    seedCatalogue();
    seedBalances();
    $first = WarehouseMovement::count();
    $qty   = WarehouseStock::sum('quantity');

    seedBalances();

    expect(WarehouseMovement::count())->toBe($first)
        ->and(WarehouseStock::sum('quantity'))->toBe($qty);
});

it('يرفض العمل على مخزنٍ عليه حركة وارد أو نقل', function () {
    // ⚠️ القياس على **رصيدٍ تغيّر بعد الجرد**: عدّ الحركات وحده لا يفرّق، فالـseeder
    //    لا يحذف إلا الحركات الافتتاحية — فتبقى حركة الوارد قائمةً رفَض أم لم يرفض.
    //    الفارق الحقيقي أن التنفيذ يُعيد الرصيد إلى رقم الورق فيمحو أثر الوارد.
    $wh = seedCatalogue();
    seedBalances();

    $item = Item::whereHas('category', fn ($q) => $q->where('name', 'مخزن التصوير'))
        ->where('name', 'حبر توشيبا')->first();

    WarehouseStock::where('warehouse_id', $wh->id)->where('item_id', $item->id)->update(['quantity' => 999]);
    WarehouseMovement::create([
        'warehouse_id' => $wh->id, 'item_id' => $item->id, 'type' => 'incoming',
        'quantity' => 976, 'balance_before' => 23, 'balance_after' => 999,
    ]);

    seedBalances();

    // لو نُفِّذ الجرد لعاد الرصيد إلى ٢٣ (رقم الورق) وضاعت الـ٩٧٦ الواردة
    expect(WarehouseStock::where('item_id', $item->id)->first()->quantity)->toBe(999);
});

it('يرفض بلا أن يمسّ السجل الافتتاحي', function () {
    // ⚠️ الفحص قبل الحذف: لو سبق الحذفُ الفحصَ لمُحي السجل ثم رُفض العمل،
    //    فبقي رصيدٌ بلا حركةٍ تُثبته — وهو ما وقع فعلاً قبل تصحيح الترتيب.
    // ⚠️ والقياس على **معرّف الصف** لا على عدده: الحذف ثم إعادة الإنشاء يُبقي
    //    العدد كما هو ويُبدّل الصفوف، فالعدّ وحده يمرّ على الحالتين.
    $wh = seedCatalogue();
    seedBalances();
    WarehouseMovement::create([
        'warehouse_id' => $wh->id, 'item_id' => Item::first()->id, 'type' => 'incoming',
        'quantity' => 5, 'balance_before' => 0, 'balance_after' => 5,
    ]);

    $openingIds = WarehouseMovement::where('type', 'opening')->orderBy('id')->pluck('id')->all();

    seedBalances();

    expect(WarehouseMovement::where('type', 'opening')->orderBy('id')->pluck('id')->all())
        ->toBe($openingIds);
});

it('يسجّل الصفر رصيداً ولا يتخطّاه', function () {
    // الشرطة في الورق تعني صفراً — وتخطّيها يُخفي الصنف من شاشة الأرصدة
    seedCatalogue();
    seedBalances();

    $registry = ItemCategory::where('name', 'فهرس التوثيق')->first();
    $marriage = Item::where('item_category_id', $registry->id)->where('name', 'سجل زواج')->first();

    expect(WarehouseStock::where('item_id', $marriage->id)->first())->not->toBeNull()
        ->and(WarehouseStock::where('item_id', $marriage->id)->first()->quantity)->toBe(0);
});

it('لا يعمل بلا مخزن رئيسي', function () {
    WarehouseType::firstOrCreate(['name' => 'فرعي'], ['level' => 3, 'order' => 3]);
    Artisan::call('db:seed', ['--class' => 'WarehouseItemsFromStatementsSeeder']);

    seedBalances();

    expect(WarehouseStock::count())->toBe(0);
});

it('يطابق مجموع الكميات ما في الورق', function () {
    seedCatalogue();
    seedBalances();

    // رقمٌ مرجعي: أي تحريف في نقل الأرقام يغيّره
    expect((int) WarehouseStock::sum('quantity'))->toBe(1689441);
});
