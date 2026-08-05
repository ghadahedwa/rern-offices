{{-- ستايل موحّد لتقارير رأي المواطن (mpdf) — نفس هوية باقي تقارير المشروع --}}
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 9pt; color: #1a1a1a; }

    .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #c9a847; padding-bottom: 5px; margin-bottom: 8px; }
    .header-table td { vertical-align: middle; padding: 2px; }
    .logo-img { width: 48px; height: 48px; }
    .app-title { font-size: 13pt; font-weight: bold; color: #c9a847; }
    .app-subtitle { font-size: 9pt; color: #666; margin-top: 1px; }
    .meta-cell { text-align: left; font-size: 8.5pt; color: #666; line-height: 1.6; }

    /* سطر الفلتر المطبَّق — تقرير بلا سياق فلتره رقم بلا معنى */
    .filter-bar { background-color: #faf6ea; border: 1px solid #ede3c2; border-radius: 3px; padding: 5px 8px; margin-bottom: 10px; font-size: 8.5pt; color: #555; }
    .filter-bar b { color: #222; }

    .sec { margin-top: 12px; page-break-inside: avoid; }
    .sec-title { font-size: 10pt; font-weight: bold; color: #b8962e; border-right: 3px solid #c9a847; padding-right: 6px; margin-bottom: 5px; }
    .sec-note { font-size: 8pt; color: #999; margin-bottom: 4px; }

    .rt { width: 100%; border-collapse: collapse; }
    .rt th { background-color: #c9a847; color: #fff; font-size: 8.5pt; font-weight: bold; padding: 5px 3px; border: 1px solid #b8962e; text-align: center; vertical-align: middle; }
    .rt td { border: 1px solid #ddd; padding: 4px 3px; font-size: 8.5pt; text-align: center; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word; }
    .rt td.rt-start { text-align: right; }
    .rt td.strong { font-weight: bold; background-color: #faf6ea; }
    .rt tbody tr:nth-child(even) td { background-color: #fafafa; }
    .rt .muted { color: #999; }

    /* شريط نسبة — mpdf لا يشغّل JS، فالمخططات كلها جداول.
       ⚠️ لا تستخدم div متداخلاً بـ background-color: mpdf يتجاهل خلفية الـ div
       الفارغ فيخرج العمود أبيض بلا شيء (تأكّد بفحص أوامر الرسم في محتوى الـPDF).
       خلفية خلية الجدول هي ما يرسمه mpdf فعلاً. */
    /* بلا width هنا: العرض يُحدَّد بالمليمتر في الـ partial (النسبة المئوية تخرج 0pt).
       ولا لون هنا: تخطيط الصفوف الزوجية أعلى أولويةً من أي class فيصبغ الشريط،
       فاللون inline داخل الـ partial. */
    .bar-t { border-collapse: collapse; }
    .bar-t td { padding: 0; border: none; height: 7px; line-height: 7px; font-size: 1pt; }

    .kpi-table { width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 4px; }
    .kpi { background-color: #faf6ea; border: 1px solid #ede3c2; border-radius: 4px; padding: 7px; text-align: center; width: 25%; }
    .kpi-value { font-size: 15pt; font-weight: bold; color: #b8962e; }
    .kpi-label { font-size: 8pt; color: #666; margin-top: 2px; }

    .quote { border-right: 2px solid #c9a847; background-color: #fbfbfb; padding: 4px 7px; margin-bottom: 4px; font-size: 8.5pt; }
    .quote .src { color: #999; font-size: 7.5pt; }

    /* بيان مفاتيح الأعمدة المختصرة — المطبوع بلا tooltip فلا بديل عنه */
    .legend { margin-top: 8px; background-color: #faf6ea; border: 1px solid #ede3c2; border-radius: 3px; padding: 5px 8px; font-size: 7.5pt; color: #666; line-height: 1.7; }
    .legend b { color: #b8962e; }

    .page-footer { margin-top: 12px; padding-top: 4px; border-top: 1px solid #e4e4e4; text-align: center; font-size: 8pt; color: #aaa; }
    .warn { color: #b45309; font-size: 8pt; margin-bottom: 6px; }
</style>
