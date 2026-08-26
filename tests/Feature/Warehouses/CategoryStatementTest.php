<?php

use App\Livewire\Warehouses\Manage\Create as ManageCreate;
use App\Livewire\Warehouses\Statement;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use App\Http\Controllers\WarehouseCategoryStatementPdfController;
use App\Reports\CategoryStatement;
use App\Reports\StatementLayout;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * «بيان بأرصدة {القسم}» — الشاشة والتقرير المطبوع.
 *
 * القواعد المحروسة هنا: البيان يشمل كل أصناف القسم (والصفر شرطة) · لا يتسرّب
 * إليه رصيد مخزن آخر · ترتيبه ترتيب الدفتر · عمود الرقم من البيانات لا من اسم
 * القسم · وحارس `warehouses.export` على الشاشة **وعلى رابط الطباعة**.
 */
function stmtUser(array $abilities = ['warehouses.index', 'warehouses.export']): User
{
    foreach (['warehouses.index', 'warehouses.export'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $role = Role::findOrCreate('stmt-'.md5(implode(',', $abilities)), 'web');
    $role->syncPermissions($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

function stmtWarehouse(string $name = 'المخزن الرئيسي', ?string $letterhead = 'الادارة العامة للتعاقدات والمخازن'): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'letterhead'        => $letterhead,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1])->id,
    ]);
}

function stmtCategory(string $name = 'مخزن التصوير', int $order = 1): ItemCategory
{
    return ItemCategory::firstOrCreate(['name' => $name], ['order' => $order]);
}

function stmtItem(ItemCategory $category, string $name, ?string $code = null, int $order = 1, bool $active = true): Item
{
    return Item::create([
        'name'             => $name,
        'code'             => $code,
        'order'            => $order,
        'is_active'        => $active,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => $category->id,
    ]);
}

it('يحجب شاشة البيان عمّن لا صلاحية تصدير له', function () {
    $this->actingAs(stmtUser(['warehouses.index']));

    $this->get(route('warehouses.statement'))->assertForbidden();
});

// وحارس mount نفسه، لا حارس الراوت وحده — كالكنترولر تماماً
it('يحرس المكوّن الصلاحية في mount', function () {
    $this->actingAs(stmtUser(['warehouses.index']));

    Livewire::test(Statement::class)->assertForbidden();
});

it('يفتح شاشة البيان لصاحب صلاحية التصدير', function () {
    $this->actingAs(stmtUser());

    $this->get(route('warehouses.statement'))->assertOk();
});

it('يشمل البيان كل أصناف القسم ويطبع الصفر شرطة', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    $has = stmtItem($cat, 'حبر توشيبا', order: 1);
    stmtItem($cat, 'حبر كانون', order: 2);   // بلا رصيد إطلاقاً
    WarehouseStock::create(['warehouse_id' => $wh->id, 'item_id' => $has->id, 'quantity' => 23]);

    $statement = CategoryStatement::build($wh, $cat);

    expect($statement['rows']->pluck('name')->all())->toBe(['حبر توشيبا', 'حبر كانون'])
        ->and($statement['rows']->pluck('quantity')->all())->toBe([23, 0])
        ->and(CategoryStatement::amount(0))->toBe('----')
        ->and(CategoryStatement::amount(23))->toBe('٢٣');
});

it('لا يتسرّب إلى البيان رصيد مخزن آخر', function () {
    $main  = stmtWarehouse('الرئيسي');
    $other = stmtWarehouse('الفرعي');
    $cat   = stmtCategory();
    $item  = stmtItem($cat, 'حبر توشيبا');
    WarehouseStock::create(['warehouse_id' => $other->id, 'item_id' => $item->id, 'quantity' => 99]);

    expect(CategoryStatement::build($main, $cat)['rows']->first()->quantity)->toBe(0);
});

it('يستبعد الأصناف الموقوفة من البيان', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    stmtItem($cat, 'صنف قائم', order: 1);
    stmtItem($cat, 'صنف موقوف', order: 2, active: false);

    expect(CategoryStatement::build($wh, $cat)['rows']->pluck('name')->all())->toBe(['صنف قائم']);
});

it('يقتصر البيان على أصناف القسم المطلوب', function () {
    $wh    = stmtWarehouse();
    $cat   = stmtCategory('مخزن التصوير', 1);
    $other = stmtCategory('مخزن المستديم', 2);
    stmtItem($cat, 'حبر توشيبا');
    stmtItem($other, 'كرسي');

    expect(CategoryStatement::build($wh, $cat)['rows']->pluck('name')->all())->toBe(['حبر توشيبا']);
});

it('يرتّب البيان بترتيب الدفتر لا أبجدياً', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    stmtItem($cat, 'ياء', order: 1);
    stmtItem($cat, 'ألف', order: 2);

    expect(CategoryStatement::build($wh, $cat)['rows']->pluck('name')->all())->toBe(['ياء', 'ألف']);
});

it('يُظهر عمود رقم الصنف حيث للأصناف أرقام ويُخفيه حيث لا أرقام', function () {
    $wh    = stmtWarehouse();
    $coded = stmtCategory('الدفتر العقاري', 1);
    $plain = stmtCategory('مخزن التصوير', 2);
    stmtItem($coded, 'شطب كلى', '٤٠ ق');
    stmtItem($plain, 'حبر توشيبا');

    expect(CategoryStatement::build($wh, $coded)['hasCodes'])->toBeTrue()
        ->and(CategoryStatement::build($wh, $plain)['hasCodes'])->toBeFalse();
});

it('يُبقي عمود المسلسل في البيان حتى مع وجود عمود رقم الصنف', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory('الدفتر العقاري');
    stmtItem($cat, 'شطب كلى', '٤٠ ق');
    $this->actingAs(stmtUser());

    // الشاشة: العمودان معاً، والمسلسل «1» غير الرقم «٤٠ ق»
    Livewire::withQueryParams(['wh' => (string) $wh->id, 'category' => (string) $cat->id])
        ->test(Statement::class)
        ->assertSee('م')
        ->assertSee('رقم الصنف')
        ->assertSee('٤٠ ق');

    // والورق: رأسا العمودين معاً في نفس الجدول
    $html = view('print.warehouse-category-statement-pdf', [
        'statement' => CategoryStatement::build($wh, $cat),
    ])->render();

    expect(substr_count($html, '>م<'))->toBeGreaterThan(0)
        ->and($html)->toContain('رقم الصنف')
        ->and($html)->toContain('٤٠ ق')
        // المسلسل بالأرقام الهندية كما في الدفتر
        ->and($html)->toContain('>١<');
});
/**
 * ⚠️ سطر الجهة **تابع المخزن لا القالب**: طبعُه ثابتاً كان يُخرج بيان مخزن
 *    المحافظة منسوباً إلى «الادارة العامة للتعاقدات والمخازن» — نسبةٌ خاطئة
 *    على ورقةٍ تُوقَّع وتُختم. والسطران الأولان فوق الجميع فيبقيان ثابتين.
 */
it('يطبع في الترويسة جهة المخزن نفسه لا جهةً ثابتة', function () {
    $branch = stmtWarehouse('مخزن أسيوط', 'مديرية الشهر العقاري والتوثيق بأسيوط');
    $cat    = stmtCategory();
    stmtItem($cat, 'حبر توشيبا');

    $html = stmtHtml($branch, $cat);

    expect($html)->toContain('مديرية الشهر العقاري والتوثيق بأسيوط')
        ->and($html)->not->toContain('الادارة العامة للتعاقدات والمخازن')
        // والسطران الأولان فوق الجميع
        ->and($html)->toContain('وزارة العدل')
        ->and($html)->toContain('مصلحة الشهر العقاري والتوثيق');
});

it('يحذف سطر الجهة لمخزنٍ بلا جهة ولا ينسبه لغيره', function () {
    $bare = stmtWarehouse('مخزن بلا جهة', null);
    $cat  = stmtCategory();
    stmtItem($cat, 'حبر توشيبا');

    $html = stmtHtml($bare, $cat);

    expect($html)->toContain('وزارة العدل')
        ->and($html)->not->toContain('الادارة العامة للتعاقدات والمخازن');
});

it('ينبّه على الشاشة قبل الطباعة إذا كان المخزن بلا جهة', function () {
    $bare = stmtWarehouse('مخزن بلا جهة', null);
    $cat  = stmtCategory();
    stmtItem($cat, 'حبر توشيبا');
    $this->actingAs(stmtUser());

    Livewire::withQueryParams(['wh' => (string) $bare->id, 'category' => (string) $cat->id])
        ->test(Statement::class)
        ->assertSee(__('home.wh_statement_no_letterhead'));
});

it('يحفظ جهة الترويسة من فورم المخزن', function () {
    Permission::findOrCreate('warehouses.settings', 'web');
    $role = Role::findOrCreate('stmt-settings', 'web');
    $role->syncPermissions(['warehouses.settings']);
    $this->actingAs(tap(User::factory()->create())->assignRole($role));

    Livewire::test(ManageCreate::class)
        ->set('name', 'مخزن سوهاج')
        ->set('warehouse_type_id', WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1])->id)
        ->set('letterhead', 'مديرية الشهر العقاري والتوثيق بسوهاج')
        ->call('save');

    expect(Warehouse::where('name', 'مخزن سوهاج')->value('letterhead'))
        ->toBe('مديرية الشهر العقاري والتوثيق بسوهاج');
});
it('يجمع إجمالي أرصدة القسم', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    $one = stmtItem($cat, 'أ', order: 1);
    $two = stmtItem($cat, 'ب', order: 2);
    WarehouseStock::create(['warehouse_id' => $wh->id, 'item_id' => $one->id, 'quantity' => 23]);
    WarehouseStock::create(['warehouse_id' => $wh->id, 'item_id' => $two->id, 'quantity' => 41]);

    expect(CategoryStatement::build($wh, $cat)['total'])->toBe(64);
});

it('يتجاهل معرّفاً تالفاً في رابط الشاشة بدل إسقاطها', function () {
    $this->actingAs(stmtUser());

    $view = Livewire::withQueryParams(['wh' => 'المخزن', 'category' => '1'])->test(Statement::class);

    expect($view->viewData('statement'))->toBeNull();
});

it('يعرض البيان على الشاشة بأصنافه وبالشرطة مكان الصفر', function () {
    $wh   = stmtWarehouse();
    $cat  = stmtCategory();
    $item = stmtItem($cat, 'حبر توشيبا', order: 1);
    stmtItem($cat, 'حبر كانون', order: 2);
    WarehouseStock::create(['warehouse_id' => $wh->id, 'item_id' => $item->id, 'quantity' => 23]);
    $this->actingAs(stmtUser());

    Livewire::withQueryParams(['wh' => (string) $wh->id, 'category' => (string) $cat->id])
        ->test(Statement::class)
        ->assertSee('حبر توشيبا')
        ->assertSee('حبر كانون')
        ->assertSee('٢٣')
        ->assertSee('----')
        // الرابط في السمة مهرَّب (&amp;) فيُفحص جزؤه الثابت
        ->assertSee('/warehouses/statement/pdf?wh='.$wh->id, false);
});
it('يحجب رابط طباعة البيان عمّن لا صلاحية تصدير له', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    $this->actingAs(stmtUser(['warehouses.index']));

    $this->get(route('warehouses.statement.pdf', ['wh' => $wh->id, 'category' => $cat->id]))
        ->assertForbidden();
});

/**
 * ⚠️ حارس الراوت (middleware) يكفي للرابط، لكنه **ليس** ما يُختبر هنا: الحارس
 *    الثاني داخل الكنترولر هو ما يبقى لو خُفِّف الراوت يوماً. واستدعاء
 *    الكنترولر مباشرةً هو الطريق الوحيد لإثباته — بلا ذلك كان حذفه يمرّ
 *    باختبارات «ناجحة» (جُرّب فعلاً فمرّ).
 */
it('يحرس كنترولر الطباعة الصلاحية بنفسه لا اعتماداً على الراوت', function () {
    $wh   = stmtWarehouse();
    $cat  = stmtCategory();
    $user = stmtUser(['warehouses.index']);

    $request = Request::create('/warehouses/statement/pdf', 'GET', [
        'wh' => $wh->id, 'category' => $cat->id,
    ]);
    $request->setUserResolver(fn () => $user);

    expect(fn () => (new WarehouseCategoryStatementPdfController)($request))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});
it('يرفض رابط الطباعة بمعرّف غير رقمي', function () {
    $this->actingAs(stmtUser());

    $this->get(route('warehouses.statement.pdf', ['wh' => 'x', 'category' => '1']))->assertNotFound();
});

it('يولّد ملف البيان فعلاً', function () {
    $wh   = stmtWarehouse();
    $cat  = stmtCategory();
    $item = stmtItem($cat, 'حبر توشيبا');
    stmtItem($cat, 'حبر كانون', order: 2);
    WarehouseStock::create(['warehouse_id' => $wh->id, 'item_id' => $item->id, 'quantity' => 23]);
    $this->actingAs(stmtUser());

    $response = $this->get(route('warehouses.statement.pdf', ['wh' => $wh->id, 'category' => $cat->id]));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

/**
 * حارس السقفين المقيسين. ⚠️ فحص الـHTML وحده لا يكشف الفيض — الفيض يقع في
 * التخطيط لا في النص، فالاختبار **يبني الملف فعلاً ويعدّ صفحاته** بأطول
 * الأسماء الفعلية (اسمٌ ينكسر سطرين هو ما أسقط التقدير الأول).
 */
function stmtLongNames(ItemCategory $category, int $count): void
{
    // ⚠️ أطول أسماء الأصناف الفعلية في البيانات المزروعة (٣٩ حرفاً)، **ومعها
    //    رقمٌ لكل صنف**: عمودا «م» و«رقم الصنف» معاً يضيّقان عمود الاسم أكثر،
    //    وهي الحالة التي تُفيض الورقة. القياس بأسماء أقصر أعطى سقفاً كاذباً.
    $names = [
        'شهادة من واقع دفتر إثبات تاريخ والتصديق',
        'نموذج ٥ شهر حكم نهائى فصل دعوى ١١٤',
        'شهادة من التصديق على التوقيعات',
        'نموذج ٢ تصرف فى محرر مشهر ١١٤',
        'الحصول على الصيغة التنفيذية',
    ];

    for ($i = 1; $i <= $count; $i++) {
        stmtItem($category, $names[$i % count($names)], '٥٤ ق م', order: $i);
    }
}

function stmtPdfPages(Warehouse $warehouse, ItemCategory $category): int
{
    $pdf = test()->get(route('warehouses.statement.pdf', [
        'wh' => $warehouse->id, 'category' => $category->id,
    ]))->getContent();

    return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
}

/** أسماء قصيرة كأسماء «مخزن المستديم» في الدفتر — «لمبا لد ١٢٠ وات». */
function stmtShortNames(ItemCategory $category, int $count): void
{
    $names = ['لمبا لد ١٢٠ وات', 'بريز تيليفون', 'زر للسقف', 'جيون خشب', 'ترباس'];

    for ($i = 1; $i <= $count; $i++) {
        stmtItem($category, $names[$i % count($names)], order: $i);
    }
}

/**
 * ⚠️ **هذا هو الانحدار الذي لاحظته المستخدمة**: الدفتر الورقي يطبع «مخزن
 *    المستديم» (٥٢ صنفاً قصيرة الأسماء) في **ورقة واحدة** بعمودين، ونظامٌ
 *    يقدّر سقف الصفوف بثابتٍ مقيسٍ على أطول الأقسام أسماءً كان يُخرجها في
 *    ورقتين نصفُ الثانية فارغ. التخطيط المُجرَّب هو ما ردّها إلى ورقة.
 */
it('يطبع قسماً بخمسين صنفاً قصير الأسماء في ورقة واحدة كالدفتر', function () {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    stmtShortNames($cat, 52);
    $this->actingAs(stmtUser());

    expect(stmtPdfPages($wh, $cat))->toBe(1);
});

/**
 * ⚠️ الحارسان التاليان يقولان معاً «لا فيض ولا ورقة زائدة»، وهما غير
 *    مترادفين: الأول يمنع صفحةً تفيض عمّا خُطِّط لها، والثاني يمنع تخطيطاً
 *    كسولاً يوزّع القسم على أوراق أكثر من اللازم (وهو عيب الثابت القديم).
 */
/**
 * ⚠️ حارس **صدق الحدّ الفيزيائي**: `ROWS_ABS_MAX_PER_COLUMN` يُسقط محاولاتٍ
 *    سلفاً بلا بناء، فلو كان أكبر من الحقيقة ضاع الوقت وحده، ولو كان **أصغر**
 *    منها أسقط تخطيطاً صالحاً فخرج البيان بورقةٍ زائدة بلا سبب ظاهر.
 *    فيُقاس بأقصر اسمٍ ممكن: الحدّ يدخل، وما فوقه بأربعة يفيض.
 */
it('يصدق الحدّ الفيزيائي لصفوف العمود: الحدّ يدخل وما فوقه يفيض', function () {
    $wh  = stmtWarehouse();
    $max = StatementLayout::ROWS_ABS_MAX_PER_COLUMN;

    // قسمٌ بعمودين ممتلئين عند الحدّ بالضبط، وآخر بعمودين فوقه بأربعة
    $atLimit = stmtCategory('عند الحدّ', 1);
    $over    = stmtCategory('فوق الحدّ', 2);
    for ($i = 1; $i <= $max * 2; $i++) {
        stmtItem($atLimit, 'زر', order: $i);
    }
    for ($i = 1; $i <= ($max + 4) * 2; $i++) {
        stmtItem($over, 'زر', order: $i);
    }

    expect(stmtLayoutPages(CategoryStatement::build($wh, $atLimit), ['twoUp' => true, 'perColumn' => $max, 'pages' => 1]))->toBe(1)
        ->and(stmtLayoutPages(CategoryStatement::build($wh, $over), ['twoUp' => true, 'perColumn' => $max + 4, 'pages' => 1]))->toBeGreaterThan(1);
});
it('لا تفيض ورقة عمّا خُطِّط لها مهما طالت الأسماء', function (int $count, bool $long) {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    $long ? stmtLongNames($cat, $count) : stmtShortNames($cat, $count);
    $statement = CategoryStatement::build($wh, $cat);

    $layout = StatementLayout::fit($count, fn (array $l) => stmtLayoutPages($statement, $l));

    expect(stmtLayoutPages($statement, $layout))->toBeLessThanOrEqual($layout['pages']);
})->with([[52, false], [44, true], [146, true]]);

it('لا يترك ورقةً زائدة: التخطيط الأضيق بورقةٍ يفيض فعلاً', function (int $count, bool $long) {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    $long ? stmtLongNames($cat, $count) : stmtShortNames($cat, $count);
    $statement = CategoryStatement::build($wh, $cat);

    $layout = StatementLayout::fit($count, fn (array $l) => stmtLayoutPages($statement, $l));

    // التخطيط السابق في ترتيب المرشَّحين — لو دخل لَما تجاوزه `fit`
    $narrower = ['twoUp' => true, 'perColumn' => (int) ceil($count / (2 * ($layout['pages'] - 1))), 'pages' => $layout['pages'] - 1];

    expect(stmtLayoutPages($statement, $narrower))->toBeGreaterThan($narrower['pages']);
})->with([[44, true], [146, true]]);
/** HTML البيان بتخطيطٍ بعينه — بلا مرور بالكنترولر. */
function stmtHtml(Warehouse $warehouse, ItemCategory $category, ?array $layout = null): string
{
    return view('print.warehouse-category-statement-pdf', [
        'statement' => CategoryStatement::build($warehouse, $category),
        'layout'    => $layout,
    ])->render();
}

/** صورة العمودين صراحةً — لتثبيت التفريعة في اختبارٍ لا يعني التخطيط. */
function stmtTwoUp(int $perColumn): array
{
    return ['twoUp' => true, 'perColumn' => $perColumn, 'pages' => 1];
}

/** عدد صفحات الملف المبنيّ بتخطيطٍ بعينه. */
function stmtLayoutPages(array $statement, array $layout): int
{
    $html = view('print.warehouse-category-statement-pdf', compact('statement', 'layout'))->render();

    $fontDirs = (new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];
    $fontData = (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'];

    $mpdf = new Mpdf\Mpdf([
        'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans',
        'margin_top' => 15, 'margin_bottom' => 15, 'margin_left' => 15, 'margin_right' => 15,
        'fontDir' => array_merge($fontDirs, [storage_path('fonts')]),
        'fontdata' => $fontData, 'tempDir' => storage_path('mpdf'),
    ]);
    $mpdf->SetDirectionality('rtl');
    $mpdf->WriteHTML($html);

    return $mpdf->page;
}

it('يوزّع عروض أعمدة البيان على ١٠٠٪ بالضبط في تفريعاته الأربع', function (bool $codes, bool $big) {
    // ⚠️ الناقص عن ١٠٠٪ يوزّعه mpdf عشوائياً، والزائد يضغط الأعمدة
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    for ($i = 1; $i <= 6; $i++) {
        stmtItem($cat, 'صنف '.$i, $codes ? '٤٠ ق' : null, order: $i);
    }

    preg_match_all('/<th style="width:([\d.]+)%"/', stmtHtml($wh, $cat, $big ? stmtTwoUp(3) : null), $m);

    expect(array_sum(array_map('floatval', $m[1])))->toBe(100.0);
})->with([[false, false], [true, false], [false, true], [true, true]]);

/**
 * ⚠️ الحارس الحقيقي لرأس العمود: يبني الـPDF ويقيس **إحداثيات الرؤوس**.
 * صفٌّ واحد = إحداثي y واحد لكل الرؤوس؛ الانكسار يُظهر إحداثيين.
 *
 * «رقم الصنف» ينكسر ثلاثة أسطر في صورة العمودين إن ضاق عمودُه، ومقيسٌ أن
 * **تصغير خط الرأس لا يمنعه**. الذي منعه هو **عرض العمود**: ١٠٪ تكسره و١٢٪
 * تُدخله سطراً، والمعتمد ١٣٪ بهامش. فأي تضييق لهذا العمود يُعاد قياسه.
 */
it('يُبقي صف رؤوس البيان في سطر واحد', function (bool $codes) {
    $wh  = stmtWarehouse();
    $cat = stmtCategory();
    stmtLongNames($cat, 36);
    if (! $codes) {
        Item::query()->update(['code' => null]);
    }
    $this->actingAs(stmtUser());

    $pdf = $this->get(route('warehouses.statement.pdf', ['wh' => $wh->id, 'category' => $cat->id]))->getContent();
    expect($pdf)->toStartWith('%PDF');

    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);
    $streams = implode('', array_map(fn ($x) => @gzuncompress($x) ?: $x, $m[1]));

    preg_match_all('/BT\s+([\d.]+)\s+([\d.]+)\s+Td/', $streams, $t, PREG_SET_ORDER);

    // أول أربعة نصوص: ثلاثة أسطر الترويسة ثم عنوان البيان، وبعدها رؤوس الأعمدة
    $headerCells = ($codes ? 4 : 3) * 2;
    $ys = array_unique(array_map(
        fn ($x) => round((float) $x[2], 1),
        array_slice($t, 4, $headerCells)
    ));

    expect($ys)->toHaveCount(1);
})->with([true, false]);
