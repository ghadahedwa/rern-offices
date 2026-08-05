<?php

use App\Models\FeedbackRating;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function ratingsPdfHtml(bool $personal = false): string
{
    Role::findOrCreate('super-admin', 'web');
    $admin  = tap(User::factory()->create())->assignRole('super-admin');
    $office = Office::factory()->public()->create();
    FeedbackRating::factory()->create([
        'office_id' => $office->id,
        'notes'     => 'ملاحظة تجريبية طويلة نسبياً لاختبار عمود الملاحظات',
    ]);

    return view('print.feedback-ratings-pdf', [
        'ratings'       => FeedbackRating::with(['office:id,name', 'governorate:id,name'])->get(),
        'total'         => 1,
        'maxRows'       => 2000,
        'personal'      => $personal,
        'trashed'       => false,
        'filters'       => new \App\Support\FeedbackResults\FeedbackFilterSet(),
        'waitTimes'     => FeedbackRating::WAIT_TIMES_SHORT,
        'criteria'      => FeedbackRating::CRITERIA,
        'criteriaShort' => FeedbackRating::CRITERIA_SHORT,
        'generatedAt'   => now(),
        'logoBase64'    => null,
    ])->render();
}

/** مجموع عروض الأعمدة لازم يساوي ١٠٠٪ بالضبط، وإلا وزّع mpdf الفائض عشوائياً. */
it('يوزّع عروض أعمدة تقرير التقييمات على ١٠٠٪ بالضبط', function (bool $personal) {
    preg_match_all('/<th style="width:([\d.]+)%"/', ratingsPdfHtml($personal), $m);

    expect(array_sum(array_map('floatval', $m[1])))->toBe(100.0);
})->with([false, true]);

it('يستخدم الأسماء المختصرة في رؤوس المحاور لا العنوان مقصوصاً', function () {
    $html = ratingsPdfHtml();

    // القصّ القديم كان ينتج «سرعة إنجاز ا» — كلمة مبتورة بلا علامة قطع
    expect($html)->toContain('>السرعة<')->toContain('>التيسير<')
        ->and($html)->not->toContain('سرعة إنجاز ا<');
});

it('يذيّل التقرير ببيان يفكّ كل اختصار', function () {
    $html = ratingsPdfHtml();

    foreach (FeedbackRating::CRITERIA as $field => [$label, $optional]) {
        expect($html)->toContain(FeedbackRating::CRITERIA_SHORT[$field].' = '.$label);
    }

    // العمودان المختصران أيضاً — الرمز بلا بيان غموض
    expect($html)->toContain(__('home.fr_criteria_avg_short').' = '.__('home.fr_criteria_avg'))
        ->and($html)->toContain(__('home.fr_export_overall_short').' = '.__('home.fr_overall_rating'));
});

/**
 * ⚠️ الحارس الحقيقي: يبني الـPDF فعلاً بصفٍّ ببيانات واقعية الطول ويقيس ارتفاع
 * صف الرؤوس. سطر واحد ≈ ١٨.٥pt، سطران ≈ ٢٨.٧pt.
 *
 * mpdf يوزّع الأعمدة حسب المحتوى ويتجاهل النِّسَب المعلَنة، فخلية المحور (رقم
 * واحد) تُضيَّق إلى أضيق حد. مقيسٌ أن توسيع العمود وتصغير الخط وتقليل الهوامش
 * و keep_table_proportions — كلها لا تمنع الانكسار؛ الرمز القصير وحده يمنعه.
 */
it('يُبقي صف رؤوس تقرير التقييمات في سطر واحد', function (bool $personal) {
    Role::findOrCreate('super-admin', 'web');
    $admin  = tap(User::factory()->create())->assignRole('super-admin');
    $office = Office::factory()->public()->create(['name' => 'توثيق المرج ثانى']);

    FeedbackRating::factory()->create([
        'office_id'   => $office->id,
        'name'        => 'منى عبد الرحمن السيد',
        'national_id' => '28106170141645',
        'phone'       => '01126287126',
        'notes'       => 'المقر نظيف والموظفون متعاونون لكن الانتظار طويل جداً',
    ]);

    $url      = route('feedback-results.ratings.pdf', $personal ? ['personal' => 1] : []);
    $response = $this->actingAs($admin)->get($url);
    $response->assertOk();

    $pdf = $response->getContent();
    expect($pdf)->toStartWith('%PDF');

    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);
    // المحتوى قد يكون مضغوطاً أو لا حسب إعداد mpdf — نقبل الحالتين
    $streams = implode('', array_map(fn ($s) => @gzuncompress($s) ?: $s, $m[1]));

    // خلفية رأس الجدول ذهبية (#c9a847) — ارتفاع مستطيلها هو ارتفاع صف الرؤوس
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

    expect($rows)->not->toBeEmpty()
        ->and(max($rows))->toBeLessThan(22.0);
})->with([false, true]);
