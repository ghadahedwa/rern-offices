<?php

use App\Livewire\DataEntry\Operators\Import;
use App\Models\DataEntryAssignment;
use App\Models\DataEntryOperator;
use App\Models\Governorate;
use App\Models\Office;
use App\Support\DataEntry\OperatorsTemplate;
use App\Support\DataEntryScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

/** مستخدم بصلاحيات ومحافظات — نسخة هذا الملف، فلا يُشترط تحميل ملف آخر معه. */
function importUser(array $abilities, array $governorates = []): App\Models\User
{
    foreach ($abilities as $ability) {
        Spatie\Permission\Models\Permission::findOrCreate($ability, 'web');
    }

    $role = Spatie\Permission\Models\Role::findOrCreate('de-import-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(App\Models\User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function importTempPath(string $suffix = '.xlsx'): string
{
    $path = tempnam(sys_get_temp_dir(), 'de_test_').$suffix;

    // ⚠️ يُحذف مع نهاية العملية لا يدوياً — الاختبار قد يسقط قبل التنظيف
    register_shutdown_function(fn () => @unlink($path));

    return $path;
}

/** يبني قالب المحافظة ويملؤه بالصفوف المعطاة، ويعيد ملفاً مرفوعاً. */
function governorateOffices(Governorate $governorate)
{
    // ⚠️ مباشرةً لا عبر DataEntryScope: النطاق يقرأ المستخدم المسجَّل، وفي اختبارات
    //    بناء القالب لا مستخدم — فيعود فارغاً ويخرج قالبٌ بلا مقرات ولا قائمة منسدلة.
    return App\Models\Office::where('governorate_id', $governorate->id)->orderBy('name')->get();
}

function filledTemplate(Governorate $governorate, array $rows): UploadedFile
{
    $template = new OperatorsTemplate($governorate, governorateOffices($governorate));

    $spreadsheet = $template->build();
    $sheet       = $spreadsheet->getSheet(0);

    $line = 2;

    foreach ($rows as [$name, $phone, $office]) {
        $sheet->setCellValue('A'.$line, $name);
        $sheet->setCellValueExplicit('B'.$line, $phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('C'.$line, $office);
        $line++;
    }

    $path = importTempPath();
    (new Xlsx($spreadsheet))->save($path);

    return UploadedFile::fake()->createWithContent('operators.xlsx', file_get_contents($path));
}

// ── القالب ───────────────────────────────────────────────

it('يبني القالب بمقرات المحافظة وحدها', function () {
    $gov   = Governorate::factory()->create();
    $mine  = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);
    Office::factory()->create(['name' => 'مقر محافظة أخرى']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    $path = (new OperatorsTemplate($gov, governorateOffices($gov)))->saveTo(importTempPath());

    $offices = IOFactory::load($path)->getSheetByName('المقرات');
    $names   = $offices->rangeToArray('A1:A5', null, false, false);

    expect(collect($names)->flatten()->filter()->values()->all())->toBe([$mine->name]);
});

it('يجعل عمود التليفون نصّياً فلا يسقط الصفر الأول', function () {
    // ⚠️ Excel يقرأ 01012345678 رقماً فيصير 1012345678 — والتنسيق يُطبَّق قبل الكتابة
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id]);

    $path  = (new OperatorsTemplate($gov, governorateOffices($gov)))->saveTo(importTempPath());
    $sheet = IOFactory::load($path)->getSheet(0);

    expect($sheet->getStyle('B2')->getNumberFormat()->getFormatCode())->toBe(NumberFormat::FORMAT_TEXT)
        ->and($sheet->getStyle('B'.(OperatorsTemplate::ROWS + 1))->getNumberFormat()->getFormatCode())
        ->toBe(NumberFormat::FORMAT_TEXT);
});

it('يضع قائمة منسدلة للمقرات على عمود المقر', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id]);

    $path  = (new OperatorsTemplate($gov, governorateOffices($gov)))->saveTo(importTempPath());
    $sheet = IOFactory::load($path)->getSheet(0);

    // ⚠️ تُقرأ من مجموعة الورقة لا بـ getCell()->getDataValidation():
    //    الأخيرة تُنشئ تحققاً فارغاً للخلية بدل أن تقرأ المحفوظ، فيمرّ الاختبار كاذباً
    $collection = $sheet->getDataValidationCollection();

    expect($collection)->toHaveKey('C2')
        ->and($collection)->toHaveKey('C'.(OperatorsTemplate::ROWS + 1))
        ->and($collection['C2']->getType())->toBe(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
        ->and($collection['C2']->getFormula1())->toContain('OfficeList');

    // والنطاق المسمّى يشير لورقة المقرات المخفية
    $named = collect(IOFactory::load($path)->getNamedRanges())->first();

    expect($named->getName())->toBe('OfficeList')
        ->and($named->getValue())->toContain('المقرات');
});

it('ينزّل القالب لصاحب الصلاحية ويمنعه عن غيره وعن محافظةٍ خارج نطاقه', function () {
    $mine   = Governorate::factory()->create();
    $others = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $mine->id]);
    Office::factory()->create(['governorate_id' => $others->id]);

    $this->actingAs(importUser(['data-entry.index'], [$mine]));
    $this->get(route('data-entry.operators.import'))->assertForbidden();

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$mine]));
    $this->get(route('data-entry.operators.import'))->assertOk();

    // محافظة ليست له: لا ملف بل خطأ على الحقل
    Livewire::test(Import::class)
        ->set('governorate', (string) $others->id)
        ->call('downloadTemplate')
        ->assertHasErrors('governorate');
});

// ── الاستيراد ────────────────────────────────────────────

it('يسكّن الصفوف الصحيحة ويحفظ صفر الهاتف', function () {
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    $file = filledTemplate($gov, [
        ['أحمد سعيد', '01012345678', 'توثيق زفتى'],
        ['هبة علي', '01199998888', 'توثيق زفتى'],
    ]);

    Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('file', $file)
        ->set('startedOn', '2026-09-15')
        ->call('import');

    $operator = DataEntryOperator::firstWhere('name', 'أحمد سعيد');

    expect(DataEntryOperator::count())->toBe(2)
        ->and($operator->phone)->toBe('01012345678')
        ->and($operator->currentAssignment->office_id)->toBe($office->id)
        ->and($operator->currentAssignment->started_on->toDateString())->toBe('2026-09-15');
});

it('يحوّل الأرقام العربية في الهاتف', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('file', filledTemplate($gov, [['سعاد', '٠١٠٩٨٧٦٥٤٣٢', 'توثيق زفتى']]))
        ->call('import');

    expect(DataEntryOperator::first()->phone)->toBe('01098765432');
});

it('يرفض صفاً بمقرٍّ ليس في المحافظة أو بلا اسم أو بهاتف خاطئ', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);
    Office::factory()->create(['name' => 'مقر محافظة أخرى']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    $component = Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('file', filledTemplate($gov, [
            ['صالح', '01012345678', 'مقر محافظة أخرى'],
            ['', '01012345679', 'توثيق زفتى'],
            ['ليلى', '12345', 'توثيق زفتى'],
            ['نادر', '01012345670', 'توثيق زفتى'],
        ]));

    $rows = collect($component->get('rows'));

    expect($rows)->toHaveCount(4)
        ->and($rows->where('status', 'error'))->toHaveCount(3)
        ->and($rows->where('status', 'ok'))->toHaveCount(1);

    $component->call('import');

    expect(DataEntryOperator::count())->toBe(1)
        ->and(DataEntryOperator::first()->name)->toBe('نادر');
});

it('يتجاوز المسجَّل بالفعل في المقر نفسه', function () {
    // ⚠️ الملف قد يُرفع مرتين سهواً فيتضاعف المدخل وحضورُه في التقرير
    $gov    = Governorate::factory()->create();
    $office = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);

    $existing = DataEntryOperator::factory()->create(['name' => 'أحمد سعيد', 'phone' => '01012345678']);
    DataEntryAssignment::create([
        'operator_id' => $existing->id,
        'office_id'   => $office->id,
        'started_on'  => '2026-09-01',
    ]);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    $component = Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('file', filledTemplate($gov, [
            ['أحمد سعيد', '01012345678', 'توثيق زفتى'],
            ['منى فؤاد', '01099998888', 'توثيق زفتى'],
        ]));

    expect(collect($component->get('rows'))->where('status', 'duplicate'))->toHaveCount(1);

    $component->call('import');

    expect(DataEntryOperator::count())->toBe(2);
});

it('يتجاهل الصفوف الفارغة في القالب', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    $component = Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('file', filledTemplate($gov, [['وحيد', '01012345678', 'توثيق زفتى']]));

    // القالب ٣٠٠ صفّ مهيَّأ، والمملوء واحد
    expect($component->get('rows'))->toHaveCount(1);
});

it('لا يحفظ شيئاً بلا قراءة ملف', function () {
    // ⚠️ النداء يصل في طلب مستقل — فلا يُحفظ إلا ما عُرض
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id]);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('rows', [[
            'line' => 2, 'name' => 'مدسوس', 'phone' => '', 'office' => 'x',
            'office_id' => Office::first()->id, 'status' => 'ok', 'message' => '',
        ]])
        ->call('import');

    expect(DataEntryOperator::count())->toBe(0);
});

it('لا يسكّن في مقرٍّ خارج النطاق ولو دُسّ في الصفوف', function () {
    $mine    = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $mine->id, 'name' => 'توثيق زفتى']);
    $outside = Office::factory()->create(['name' => 'مقر بعيد']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$mine]));

    $component = Livewire::test(Import::class)
        ->set('governorate', (string) $mine->id)
        ->set('file', filledTemplate($mine, [['وحيد', '01012345678', 'توثيق زفتى']]));

    $rows = $component->get('rows');
    $rows[0]['office_id'] = $outside->id;       // معرّف مدسوس بين الطلبين

    $component->set('rows', $rows)->call('import');

    expect(DataEntryOperator::count())->toBe(0);
});

it('يمنع الاستيراد عمّن سُحبت صلاحيته والشاشة مفتوحة', function () {
    $gov = Governorate::factory()->create();
    Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);

    $user = importUser(['data-entry.index', 'data-entry.create'], [$gov]);
    $this->actingAs($user);

    $component = Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->set('file', filledTemplate($gov, [['وحيد', '01012345678', 'توثيق زفتى']]));

    // ⚠️ removeRole لا detach: الأخيرة تتجاوز كاش صلاحيات Spatie فيبقى المستخدم مصرَّحاً له
    $user->removeRole($user->roles->first());

    $component->call('import')->assertForbidden();

    expect(DataEntryOperator::count())->toBe(0);
});

it('لا يضع في القالب المُنزَّل إلا مقرات المحافظة المختارة', function () {
    // ⚠️ الفحص على القالب الخارج من الشاشة لا على الكلاس وحده: الشاشة هي التي
    //    تحصر المقرات بالنطاق، وبناءُ القالب مباشرةً في الاختبار يتجاوز ذلك الحصر.
    $gov  = Governorate::factory()->create();
    $mine = Office::factory()->create(['governorate_id' => $gov->id, 'name' => 'توثيق زفتى']);
    Office::factory()->create(['name' => 'مقر محافظة أخرى']);

    $this->actingAs(importUser(['data-entry.index', 'data-entry.create'], [$gov]));

    $response = Livewire::test(Import::class)
        ->set('governorate', (string) $gov->id)
        ->instance()
        ->downloadTemplate();

    $sheet = IOFactory::load($response->getFile()->getPathname())->getSheetByName('المقرات');
    $names = collect($sheet->rangeToArray('A1:A10', null, false, false))->flatten()->filter()->values();

    expect($names->all())->toBe([$mine->name]);
});
