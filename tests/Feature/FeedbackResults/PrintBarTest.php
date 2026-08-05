<?php

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * حارس مِزلقتَي mpdf في شريط النسبة — كلتاهما تُخرج عموداً يبدو فارغاً:
 *  ١) الـ div ذو background-color لا يُرسم أصلاً.
 *  ٢) الجدول المتداخل داخل خلية لا تُحسب نسبته المئوية فيخرج بعرض 0pt.
 *
 * ⚠️ **الفحص على أبعاد المستطيل المرسوم، لا على وجود اللون.** اختبار «هل اللون
 * موجود؟» يمرّ والشريط بعرض صفر (حصل فعلاً)، واختبار على التقرير كاملاً يمرّ
 * أيضاً لأن رؤوس الجداول تستخدم نفس اللون الذهبي.
 */

/**
 * أبعاد أول مستطيل مرسوم باللون الذهبي: ['w' => .., 'h' => ..] أو null.
 * $rowsBefore يضع صفوفاً قبل صف الشريط لاختباره في موضع مخطَّط (زوجي).
 */
function goldRect(string $barHtml, int $rowsBefore = 0): ?array
{
    $mpdf = new Mpdf([
        'mode'         => 'utf-8',
        'format'       => 'A4',
        'default_font' => 'dejavusans',
        'fontDir'      => (new ConfigVariables())->getDefaults()['fontDir'],
        'fontdata'     => (new FontVariables())->getDefaults()['fontdata'],
        'tempDir'      => storage_path('mpdf'),
    ]);
    $mpdf->SetDirectionality('rtl');
    $mpdf->SetCompression(false);   // بلا ضغط حتى تُقرأ أوامر الرسم نصاً

    // الشريط داخل خلية جدول تماماً كما يُستعمل في التقرير — الخلية هي موضع المِزلقة.
    // ⚠️ بلا <thead>: خلفية رأس الجدول ذهبية أيضاً، فوجودها يجعل الفحص يلتقطها
    //    بدل الشريط فيمرّ الاختبار والشريط بعرض صفر (وقع هذا فعلاً).
    $filler = str_repeat('<tr><td>صف</td><td></td></tr>', $rowsBefore);

    $mpdf->WriteHTML(
        view('print.includes.feedback-styles')->render()
        .'<table class="rt"><tbody>'.$filler.'<tr>'
        .'<td style="width:70%">سرعة الإنجاز</td><td style="width:30%">'.$barHtml.'</td>'
        .'</tr></tbody></table>'
    );

    $pdf = $mpdf->Output('', 'S');

    // #c9a847 = rgb(201,168,71) → 0.788 0.659 0.278 rg ثم "x y w h re f"
    return preg_match('/0\.78\d* 0\.65\d* 0\.27\d* rg\s+[\d.-]+ [\d.-]+ ([\d.-]+) ([\d.-]+) re/', $pdf, $m)
        ? ['w' => abs((float) $m[1]), 'h' => abs((float) $m[2])]
        : null;
}

function bar(int $percent, ?int $width = null): string
{
    return view('print.includes.feedback-bar', array_filter([
        'percent' => $percent,
        'width'   => $width,
    ], fn ($v) => $v !== null))->render();
}

it('يرسم شريط النسبة بعرض وارتفاع حقيقيين لا بصفر', function () {
    $rect = goldRect(bar(80, 40));

    expect($rect)->not->toBeNull()
        // ٨٠٪ من ٤٠مم = ٣٢مم ≈ ٩٠.٧pt — الاختبار السابق كان يكتفي بوجود اللون فمرّ بعرض صفر
        ->and($rect['w'])->toBeGreaterThan(80.0)
        ->and($rect['h'])->toBeGreaterThan(3.0);
});

it('يتناسب عرض الجزء الممتلئ مع النسبة', function () {
    $quarter = goldRect(bar(25, 40));
    $full    = goldRect(bar(100, 40));

    expect($quarter['w'])->toBeGreaterThan(20.0)
        ->and($full['w'])->toBeGreaterThan($quarter['w'] * 3);
});

it('لا يرسم جزءاً ممتلئاً عند صفر بالمئة', function () {
    expect(goldRect(bar(0, 40)))->toBeNull();
});

/**
 * ⚠️ صف زوجي = صف مخطَّط. قاعدة `.rt tbody tr:nth-child(even) td` مُحدِّد نَسَبي
 * فتطال خلايا الجدول المتداخل أيضاً، وأولويتها أعلى من أي class على الشريط —
 * فكان الشريط يُصبغ بلون التخطيط ويظهر ويختفي بالتناوب صفاً بعد صف.
 * لذلك لون الشريط inline.
 */
it('يرسم الشريط بلونه داخل صف مخطَّط لا بلون التخطيط', function () {
    $odd  = goldRect(bar(80, 40), rowsBefore: 0);   // صف فردي — بلا تخطيط
    $even = goldRect(bar(80, 40), rowsBefore: 1);   // صف زوجي — مخطَّط

    expect($even)->not->toBeNull()
        ->and($even['w'])->toBe($odd['w'])
        ->and($even['h'])->toBe($odd['h']);
});
