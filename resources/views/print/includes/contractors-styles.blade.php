{{--
    ستايل تقارير العاملين بالتعاقد المطبوعة (mpdf) — نفس هوية باقي تقارير المشروع.

    ⚠️ **مستقلٌّ عن `feedback-styles` عمداً** وإن تشابها: ملفٌ مشترك يجعل تعديلاً
       لحاجةٍ في موديولٍ يكسر تخطيط موديولٍ آخر بلا أثرٍ ظاهر، وتخطيط جداول mpdf
       العربية يُقاس لا يُقدَّر.
--}}
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

    .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 8px; }
    .header-table td { vertical-align: middle; padding: 2px; }
    .logo-img { width: 48px; height: 48px; }
    .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
    .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
    .meta-cell { text-align: left; font-size: 8.5pt; color: #666; line-height: 1.6; }

    /* سطر الفلتر المطبَّق — تقريرٌ بلا سياق فلتره رقمٌ بلا معنى */
    .filter-bar { background-color: #faf6ea; border: 1px solid #ede3c2; border-radius: 3px; padding: 5px 8px; margin-bottom: 6px; font-size: 8.5pt; color: #555; }
    .filter-bar b { color: #222; }

    /* تفكيك أيام العمل — يُعرض التفكيك لا الرقم النهائي وحده */
    .breakdown { border: 1px solid #e4e4e4; border-radius: 3px; padding: 5px 8px; margin-bottom: 8px; font-size: 8.5pt; color: #444; }
    .breakdown b { color: #b8962e; }
    .breakdown .holidays { color: #888; font-size: 7.5pt; margin-top: 2px; }

    .sec { margin-top: 10px; }
    .sec-title { font-size: 10pt; font-weight: bold; color: #b8962e; border-right: 3px solid #c9a847; padding-right: 6px; margin-bottom: 5px; }

    .rt { width: 100%; border-collapse: collapse; }
    /*
       ⚠️ **`nowrap` على الرأس — مقيسٌ لا مقدَّر**: اسم حالةٍ من كلمتين («إجازة مرضية»)
          في عمودٍ ضيّق يكسر **صفّ الرؤوس كله** سطرين (٢٨.٦٥pt بدل ١٨.٤٥pt)، والاسم
          يكتبه المدير من شاشة الحالات فلا يُقصَر من الكود.
          ومقيسٌ أن تصغير الخط لا يمنعه (٧.٥pt ← ٢٦.٢٥ · ٧pt ← ٢٥.٠٥) ولا تقليل
          الهوامش، **وأن `nowrap` لا يُخرج الجدول عن الصفحة** (٧٩٥.٨pt في الحالتين:
          mpdf يعيد توزيع الأعمدة ولا يفيض).
       ⚠️ ولا تُعمَّم القاعدة على تقارير رأي المواطن: هناك الرأسُ المنكسر في عمودٍ
          رقميٍّ ضيّق و`nowrap` لم ينفع — الفرق في المحتوى لا في mpdf. **يُقاس لكل جدول.**
    */
    .rt th { background-color: #c9a847; color: #fff; font-size: 8.5pt; font-weight: bold; padding: 5px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle; white-space: nowrap; }
    .rt td { border: 1px solid #ddd; padding: 4px 3px; font-size: 8.5pt; text-align: center; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word; }
    .rt td.rt-start { text-align: right; }
    .rt td.sub { font-size: 7.5pt; color: #999; }
    .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
    .rt tfoot td { background-color: #faf6ea; font-weight: bold; border-top: 2px solid #c9a847; }
    .rt .muted { color: #bbb; }

    /* تفصيل أيام الغياب والإجازة — حبّاتٌ لا جدول، فالعدد متغيّر */
    .days { font-size: 8pt; line-height: 1.9; }
    .days .day { border: 1px solid #ddd; border-radius: 3px; padding: 1px 5px; margin-left: 3px; }

    .equation { margin-top: 6px; font-size: 7.5pt; color: #999; }
    .warn { color: #b45309; font-size: 8pt; margin-bottom: 6px; }
    .empty { text-align: center; color: #999; font-size: 9pt; padding: 20px 0; }
    .page-footer { margin-top: 12px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
</style>
