{{--
    «بيان بأرصدة {القسم}» بصورة الدفتر الورقي.

    ⚠️ الورق نفسه على صورتين رآهما المستخدم: قسمٌ قليل الأصناف يُطبع عموداً
       واحداً بعمود «م»، وقسمٌ كثيرها (الدفتر العقاري) يُطبع **عمودين
       متجاورين** بعمود «رقم الصنف» ليدخل في ورقة واحدة. الصورتان هنا،
       ويختار بينهما عدد الأصناف ووجود الأرقام — لا اسم القسم.

    ⚠️ ولا خانة لاسم أمين المخزن: يُطبع العنوان ويمضي هو بخطّ يده (قرار
       المستخدمة ٢٠٢٦-٠٨-٢٦)، فلا اسم يُخزَّن ولا يُكتب في الشاشة.
--}}
@php
    use App\Reports\CategoryStatement;

    $rows     = $statement['rows']->values();
    $hasCodes = $statement['hasCodes'];

    // ⚠️ التخطيط **يأتي مُجرَّباً من StatementLayout** ولا يُحسب هنا: لا عدد
    //    الصفوف ولا عدد الأحرف يتنبّأ بالفيض (التفصيل في الكلاس نفسه).
    $layout    = $layout ?? App\Reports\StatementLayout::candidates($rows->count())->current();
    $twoUp     = $layout['twoUp'];
    $perColumn = max((int) $layout['perColumn'], 1);
    $perPage   = $twoUp ? $perColumn * 2 : max($rows->count(), 1);

    $pages = $rows->chunk($perPage)->values();
    $year  = \App\Support\ArabicDigits::toArabic((string) \App\Support\LocalTime::at(now())->year);
    $size  = $twoUp ? '8.5pt' : '9.5pt';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('home.wh_statement_title', ['category' => $statement['category']->name]) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: dejavusans, sans-serif; direction: rtl; font-size: 10pt; color: #000; }

        /* ⚠️ جدول لا div: القاعدة التي تضع الـblock يميناً في RTL تعتمد على
           هامشٍ يُعدَّل تلقائياً، وmpdf لا يطبّقها كالمتصفح — فكان النصّ يقع
           يسار الورقة. الخليّة الأولى في RTL يمينٌ بلا التباس. */
        .head { width: 100%; }
        .head td.letterhead { width: 62%; text-align: center; line-height: 1.9; font-weight: bold; font-size: 11pt; }
        .head td.spacer { width: 38%; }
        .doc-title { text-align: center; font-weight: bold; font-size: 11pt; text-decoration: underline; margin: 14px 0 16px; }

        .st { width: 100%; border-collapse: collapse; }
        .st th, .st td { border: 1px solid #000; padding: 4px 5px; vertical-align: middle; }
        /* ⚠️ رأس العمود يتبع خط الصفوف في صورة العمودين: «رقم الصنف» بـ١٠pt
           كان ينكسر سطرين في عمودٍ عرضه ١٨مم — mpdf يضيّق العمود على محتواه
           ويتجاهل النسبة المعلَنة، فالانكسار لا يُصلحه توسيع العمود. */
        .st th { font-weight: bold; text-align: center; font-size: {{ $twoUp ? '8.5pt' : '10pt' }}; }
        .st td.lead { text-align: center; font-size: {{ $size }}; }
        .st td.name { text-align: right;  font-size: {{ $size }}; }
        .st td.qty  { text-align: center; font-size: {{ $size }}; }
        /* الخلية الفارغة في ذيل العمود الأيسر: بلا حدود حتى لا تُرسم صفوفاً وهمية */
        .st td.blank { border: none; }

        .regards { text-align: center; font-weight: bold; font-size: 10pt; margin-top: 10px; }
        /* «تحريرا في» على يمين الورقة كما في الأصل، لا يسارها */
        .written { text-align: right; font-weight: bold; font-size: 11pt; margin-top: 22px; }
        .signs { width: 100%; margin-top: 34px; }
        .signs td { font-weight: bold; font-size: 11pt; padding-bottom: 34px; }
        .signs td.right { text-align: right; }
        .signs td.left  { text-align: left; }
    </style>
</head>
<body>

@foreach($pages as $pageIndex => $pageRows)
    @php
        $offset = $pageIndex * $perPage;
        $group  = $pageRows->values();
        $right  = $twoUp ? $group->take($perColumn) : $group;
        $left   = $twoUp ? $group->slice($perColumn)->values() : collect();
        $lines  = $right->count();
    @endphp

    <table class="head">
        <tr>
            <td class="letterhead">
                <div>{{ __('home.wh_statement_ministry') }}</div>
                <div>{{ __('home.wh_statement_authority') }}</div>
                {{-- جهة هذا المخزن؛ فارغةً يُحذف السطر ولا يُستبدل بجهة غيره --}}
                @if(filled($statement['warehouse']->letterhead))
                    <div>{{ $statement['warehouse']->letterhead }}</div>
                @endif
            </td>
            <td class="spacer"></td>
        </tr>
    </table>

    <div class="doc-title">{{ __('home.wh_statement_title', ['category' => $statement['category']->name]) }}</div>

    <table class="st">
        <thead>
            <tr>
                {{-- ⚠️ مجموع النِسَب ١٠٠٪ بالضبط في كل تفريعة — الناقص يوزّعه mpdf عشوائياً --}}
                @if($twoUp)
                    {{-- الترتيب من اليمين: المجموعة اليمنى ثم اليسرى، كما في الدفتر --}}
                    @foreach([0, 1] as $group)
                        <th style="width:{{ $hasCodes ? '5%' : '7%' }}">{{ __('home.wh_statement_serial') }}</th>
                        @if($hasCodes)
                            <th style="width:10%">{{ __('home.wh_statement_code_short') }}</th>
                        @endif
                        <th style="width:{{ $hasCodes ? '25%' : '26%' }}">{{ __('home.item_name') }}</th>
                        <th style="width:{{ $hasCodes ? '10%' : '17%' }}">{{ __('home.wh_statement_count_print') }}</th>
                    @endforeach
                @else
                    <th style="width:{{ $hasCodes ? '8%' : '12%' }}">{{ __('home.wh_statement_serial') }}</th>
                    @if($hasCodes)
                        <th style="width:15%">{{ __('home.item_code') }}</th>
                    @endif
                    <th style="width:{{ $hasCodes ? '47%' : '53%' }}">{{ __('home.item_name') }}</th>
                    <th style="width:{{ $hasCodes ? '30%' : '35%' }}">{{ __('home.wh_statement_count_print') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $lines; $i++)
                @php $a = $right->get($i); $b = $left->get($i); @endphp
                <tr>
                    <td class="lead">{{ \App\Support\ArabicDigits::toArabic((string) ($offset + $i + 1)) }}</td>
                    @if($hasCodes)<td class="lead">{{ $a->code }}</td>@endif
                    <td class="name">{{ $a->name }}</td>
                    <td class="qty">{{ CategoryStatement::amount((int) $a->quantity) }}</td>
                    @if($twoUp)
                        @php $blank = $b ? '' : 'blank'; @endphp
                        <td class="lead {{ $blank }}">{{ $b ? \App\Support\ArabicDigits::toArabic((string) ($offset + $perColumn + $i + 1)) : '' }}</td>
                        @if($hasCodes)<td class="lead {{ $blank }}">{{ $b?->code }}</td>@endif
                        <td class="name {{ $blank }}">{{ $b?->name }}</td>
                        <td class="qty  {{ $blank }}">{{ $b ? CategoryStatement::amount((int) $b->quantity) : '' }}</td>
                    @endif
                </tr>
            @endfor
        </tbody>
    </table>

    @if($loop->last)
        <div class="regards">{{ __('home.wh_statement_regards') }}</div>
        <div class="written">{{ __('home.wh_statement_written_on', ['year' => $year]) }}</div>

        {{-- عنوانان بلا اسم: يمضي كلٌّ منهما بخطّ يده تحت عنوانه --}}
        <table class="signs">
            <tr>
                <td class="right">{{ __('home.wh_statement_keeper') }}</td>
                <td class="left">{{ __('home.wh_statement_manager') }}</td>
            </tr>
        </table>
    @else
        <pagebreak />
    @endif
@endforeach

</body>
</html>
