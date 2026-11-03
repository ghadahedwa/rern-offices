<?php

namespace App\Support;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/**
 * خطوط تقارير mpdf — المصدر الواحد. الكنترولر يدمج `config()` في إعداداته بدل تكرار fontDir/fontdata.
 *
 * ⚠️ خط Khalid Art نسخة معدّلة (`storage/fonts/khalid-art-mpdf.ttf`): mpdf لا يوصّل العربي إلا بـuseOTL،
 * والملف الأصلي لا يحتمله (لا GDEF، وligatures بلا Unicode تُسقطه، وmset تكسر الحروف). فأُضيف GDEF
 * كل حروفه أساسية وحُذفت liga وmset من قائمة الخط، وبقي init/medi/fina — مُطابَق بالعين مع رسم Chrome.
 * وأشكال `%` و`,` و`=` منقولةٌ من DejaVu إلى الملف (قرار العميلة: كانت «٪» عربية · «1،250» · «=» كشرطة)،
 * و`−` مضافةٌ منه — **داخل الخط لا بالاحتياط**: حرفٌ ناقص داخل خلية جدول يرسمه mpdf مربعاً (.notdef) ثم
 * يكرّره بخط الاحتياط بجواره («0□%»). فأي رمز ناقص يظهر في تقرير يُنقل إلى الملف بالطريقة نفسها.
 * للخط وزنٌ واحد، وfont-weight: bold يُرسم تعريضاً صناعياً (`falseBoldWeight` في mpdf) كـWord.
 * وبعد أي تعديل على ملف الخط: امسح `storage/mpdf/mpdf/ttfontdata/khalidart.*` (mpdf يخزّنه بالاسم).
 */
class PdfFonts
{
    public const DEFAULT = 'khalidart';

    public static function config(): array
    {
        $fontData = (new FontVariables())->getDefaults()['fontdata'];
        $fontData['khalidart'] = ['R' => 'khalid-art-mpdf.ttf', 'useOTL' => 0xFF];

        return [
            'default_font'     => self::DEFAULT,
            // حرفٌ ليس في الخط (علامة الطرح − في تفكيك أيام العمل) يُرسم بـDejaVu لا مربعاً فارغاً
            'useSubstitutions' => true,
            'backupSubsFont'   => ['dejavusans'],
            'fontDir'      => array_merge((new ConfigVariables())->getDefaults()['fontDir'], [storage_path('fonts')]),
            'fontdata'     => $fontData,
        ];
    }
}
