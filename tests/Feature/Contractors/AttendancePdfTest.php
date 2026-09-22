<?php

use App\Models\Contractor;
use App\Models\ContractorAssignment;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function pdfUser(array $governorates = [], array $abilities = ['contractors.index', 'contractors.export']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('pdf-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());

    return $user->fresh();
}

function pdfOffice(?Governorate $governorate = null, string $name = 'مكتب التقرير'): Office
{
    return Office::factory()->create([
        'governorate_id' => ($governorate ?? Governorate::factory()->create())->id,
        'name'           => $name,
    ]);
}

function pdfWorker(Office $office, string $name = 'عامل التقرير', string $from = '2026-09-01'): Contractor
{
    $contractor = Contractor::factory()->create(['name' => $name]);

    ContractorAssignment::create([
        'contractor_id' => $contractor->id,
        'office_id'     => $office->id,
        'started_on'    => $from,
    ]);

    return $contractor->fresh();
}

function pdfUrl(array $params = []): string
{
    return route('contractors.reports.pdf', $params + [
        'level' => 'governorates',
        'from'  => '2026-09-01',
        'to'    => '2026-09-30',
    ]);
}

// ── الحراسة ─────────────────────────────────────────────────────────────

it('يمنع تقرير الـPDF بلا contractors.export', function () {
    $gov = Governorate::factory()->create();
    pdfWorker(pdfOffice($gov));

    // ⚠️ الرابط يُفتح منسوخاً في تبويبٍ آخر، فالحارس داخل الكنترولر لا في الشاشة.
    $this->actingAs(pdfUser([$gov], ['contractors.index']))
        ->get(pdfUrl(['gov' => $gov->id]))
        ->assertForbidden();
});

it('يمنعه عن غير المسجَّل', function () {
    $this->get(pdfUrl())->assertRedirect();
});

// ── فحص ما يصل من الرابط ────────────────────────────────────────────────

it('يرفض مستوىً خارج القائمة البيضاء', function () {
    $gov = Governorate::factory()->create();

    $this->actingAs(pdfUser([$gov]))
        ->get(route('contractors.reports.pdf', ['level' => 'everything', 'from' => '2026-09-01', 'to' => '2026-09-30']))
        ->assertNotFound();
});

it('يرفض تاريخاً تالفاً من الرابط', function () {
    $gov = Governorate::factory()->create();

    $this->actingAs(pdfUser([$gov]))
        ->get(route('contractors.reports.pdf', ['level' => 'governorates', 'from' => 'مش تاريخ', 'to' => '2026-09-30']))
        ->assertNotFound();
});

// ── التوليد ─────────────────────────────────────────────────────────────

it('يولّد PDF فعلياً للمستويات الثلاثة', function (string $level) {
    $gov    = Governorate::factory()->create(['name' => 'القاهرة']);
    $office = pdfOffice($gov);
    $worker = pdfWorker($office);

    $params = ['level' => $level, 'gov' => $gov->id];

    if ($level === 'office') {
        $params['office'] = $office->id;
    }

    if ($level === 'contractor') {
        $params['contractor'] = $worker->id;
    }

    $response = $this->actingAs(pdfUser([$gov]))->get(pdfUrl($params));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
})->with(['governorates', 'office', 'contractor']);

// ── النطاق ──────────────────────────────────────────────────────────────

it('لا يُخرج في التقرير المطبوع أيام محافظةٍ خارج النطاق', function () {
    $mine   = Governorate::factory()->create(['name' => 'محافظتي']);
    $theirs = Governorate::factory()->create(['name' => 'محافظة أخرى']);

    pdfWorker(pdfOffice($mine), 'عاملي');
    pdfWorker(pdfOffice($theirs), 'عامل غيري');

    // ⚠️ `?gov=` لمحافظةٍ ليست له تُهمَل ولا تُمرَّر
    $content = $this->actingAs(pdfUser([$mine]))
        ->get(pdfUrl(['gov' => $theirs->id]))
        ->getContent();

    expect(substr($content, 0, 4))->toBe('%PDF');
});

it('يبني التقرير المطبوع من صفوف الشاشة نفسها', function () {
    $gov    = Governorate::factory()->create();
    $office = pdfOffice($gov);
    $worker = pdfWorker($office);

    $user = pdfUser([$gov]);
    $this->actingAs($user);

    // الأرقام تُقرأ من الاستعلام المشترك لا من حسابٍ في الكنترولر
    $query = new App\Support\Contractors\AttendanceReportQuery(
        level: 'governorates',
        from: Carbon\CarbonImmutable::parse('2026-09-01'),
        to: Carbon\CarbonImmutable::parse('2026-09-30'),
        governorateIds: [$gov->id],
        user: $user,
    );

    $rows = $query->rows();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['working'])->toBe(26)
        ->and($rows[0]['contractor_id'])->toBe($worker->id);
});

// ── تخطيط الجدول المطبوع ────────────────────────────────────────────────

/**
 * ارتفاعات المستطيلات المرسومة بلون الرأس الذهبي (#c9a847) في أوامر رسم الـPDF.
 *
 * ⚠️ **يقيس الـPDF المبنيّ فعلاً لا نصّ الـHTML**: انكسار الرأس سطرين يقع في
 *    **التخطيط** لا في النص، فلا يكشفه فحص الـHTML. (درس `RatingsPdfLayoutTest`.)
 */
function pdfGoldRowHeights(string $pdf): array
{
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);
    $streams = implode('', array_map(fn ($s) => @gzuncompress($s) ?: $s, $m[1]));

    preg_match_all('/([\d.]+) ([\d.]+) ([\d.]+) rg|([\d.-]+) ([\d.-]+) ([\d.-]+) ([\d.-]+) re/', $streams, $ops, PREG_SET_ORDER);

    $gold = false;
    $rows = [];

    foreach ($ops as $op) {
        if (($op[1] ?? '') !== '') {
            $gold = round((float) $op[1], 2) === 0.79 && round((float) $op[2], 2) === 0.66;
        } elseif ($gold) {
            $rows[] = abs((float) $op[7]);
        }
    }

    return $rows;
}

it('لا ينكسر رأس الجدول المطبوع سطرين', function (string $level) {
    $gov    = Governorate::factory()->create(['name' => 'الإسماعيلية']);
    $office = pdfOffice($gov, 'مكتب توثيق وشهر عقاري الإسماعيلية أول');
    $worker = pdfWorker($office, 'عبد الرحمن محمد عبد الله');

    $params = ['level' => $level, 'gov' => $gov->id];

    if ($level === 'office') {
        $params['office'] = $office->id;
    }

    if ($level === 'contractor') {
        $params['contractor'] = $worker->id;
    }

    $pdf = $this->actingAs(pdfUser([$gov]))->get(pdfUrl($params))->getContent();

    // ⚠️ «عدد العاملين» و«غير مراجَع» أعرضُ ما في الرأس، ولذلك التقرير **أفقيّ**:
    //    في الوضع الرأسي تضيق أعمدة الأرقام فينكسر رأساهما سطرين.
    //    سطرٌ واحد ≈ ١٨.٥pt · سطران ≈ ٢٨.٧pt (مقيسٌ بالخط نفسه).
    $heights = pdfGoldRowHeights($pdf);

    expect($heights)->not->toBeEmpty()
        ->and(max($heights))->toBeLessThan(22.0);
})->with(['governorates', 'office', 'contractor']);

it('يوزّع عروض أعمدة التقرير المطبوع على ١٠٠٪ بالضبط', function (string $level) {
    // ⚠️ الناقص يوزّعه mpdf عشوائياً فتنكسر الرؤوس، وعدد أعمدة الحالات متغيّر.
    $gov    = Governorate::factory()->create();
    $office = pdfOffice($gov);
    $worker = pdfWorker($office);

    $query = new App\Support\Contractors\AttendanceReportQuery(
        level: $level,
        from: Carbon\CarbonImmutable::parse('2026-09-01'),
        to: Carbon\CarbonImmutable::parse('2026-09-30'),
        governorateIds: [$gov->id],
        officeId: $level === 'office' ? $office->id : null,
        contractorId: $level === 'contractor' ? $worker->id : null,
        user: pdfUser([$gov]),
    );

    $rows = $query->rows();

    $html = view('print.contractors-attendance-pdf', [
        'query'       => $query,
        'level'       => $level,
        'statuses'    => App\Support\Contractors\AttendanceReport::statusColumns($rows),
        'breakdown'   => $query->report()->breakdown(),
        'holidays'    => $query->report()->holidays(),
        'totals'      => App\Support\Contractors\AttendanceReport::sum($rows),
        'capped'      => false,
        'maxRows'     => 2000,
        'title'       => 'عنوان',
        'generatedAt' => now(),
        'logoBase64'  => null,
        'groups'      => App\Support\Contractors\AttendanceReport::groupBy($rows, 'governorate_id'),
        'rows'        => $rows,
        'contractors' => 1,
        'subject'     => $query->subject(),
        'exceptions'  => [],
    ])->render();

    preg_match_all('/<th style="width:([\d.]+)%"/', $html, $m);

    expect(array_sum(array_map('floatval', $m[1])))->toBe(100.0);
})->with(['governorates', 'office', 'contractor']);

it('يتحوّل أفقياً حين تزيد أعمدة الحالات فلا ينكسر الرأس', function () {
    // ⚠️ المدير يضيف حالاتٍ من شاشة الحالات، فعدد الأعمدة ليس ثابتاً.
    //    وبلا التحوّل الأفقي تضيق أعمدة الأرقام وينكسر «عدد العاملين» سطرين.
    foreach (['مأمورية', 'ندب', 'إجازة مرضية'] as $name) {
        App\Models\AttendanceStatus::create(['name' => $name, 'color' => '#888888', 'order' => 9, 'is_active' => true]);
    }

    $gov    = Governorate::factory()->create(['name' => 'الإسماعيلية']);
    $office = pdfOffice($gov, 'مكتب توثيق وشهر عقاري الإسماعيلية أول');
    pdfWorker($office, 'عبد الرحمن محمد عبد الله');

    $pdf = $this->actingAs(pdfUser([$gov]))->get(pdfUrl(['gov' => $gov->id]))->getContent();

    expect(max(pdfGoldRowHeights($pdf)))->toBeLessThan(22.0);
});

it('يُهمل معرّفاً تالفاً في الرابط ولا يقرؤه رقماً', function () {
    // ⚠️ «12x» بالتحويل المجرّد يصير ١٢ — عاملاً حقيقياً غير المقصود.
    $gov    = Governorate::factory()->create();
    $office = pdfOffice($gov);
    $worker = pdfWorker($office, 'عامل حقيقي');

    $user = pdfUser([$gov]);

    $query = App\Support\Contractors\AttendanceReportQuery::fromRequest(
        Illuminate\Http\Request::create('/x', 'GET', [
            'level'      => 'contractor',
            'from'       => '2026-09-01',
            'to'         => '2026-09-30',
            'contractor' => $worker->id.'x',
            'gov'        => $gov->id.'x',
        ]),
        $user
    );

    expect($query->contractorId)->toBeNull()
        ->and($query->governorateIds)->toBe([])
        ->and($query->subject())->toBeNull();
});
