{{-- شريط نسبة لتقارير mpdf. $percent من ٠ إلى ١٠٠، و$width عرضه بالمليمتر.

     ⚠️ ثلاث مِزالق، كلها تُخرج عموداً يبدو فارغاً (وكلها مقيسة في PrintBarTest):
     ١) الـ div ذو background-color لا يرسمه mpdf أصلاً — لذلك الشريط خلايا جدول.
     ٢) الجدول المتداخل داخل خلية **لا تُحسب نسبته المئوية** فيخرج بعرض 0pt،
        لذلك العرض هنا **مطلق بالمليمتر**.
     ٣) **لون الشريط inline لا بـ class**: قاعدة تخطيط الصفوف
        `.rt tbody tr:nth-child(even) td` تطال خلايا الجدول المتداخل أيضاً
        (المُحدِّد النَّسَبي) وأولويتها أعلى من .bar-on، فتصبغ الشريط بلون
        التخطيط في الصفوف الزوجية — أي شريط يظهر ويختفي بالتناوب. --}}
@php
    $pct   = max(0, min(100, (int) round($percent)));
    $width = $width ?? 40;
    $on    = round($width * $pct / 100, 2);
    $off   = round($width - $on, 2);
@endphp
<table class="bar-t" style="width: {{ $width }}mm">
    <tr>
        {{-- خلية بعرض صفر تُحذف لا تُرسم --}}
        @if($on > 0)
            <td style="width: {{ $on }}mm; background-color: #c9a847"></td>
        @endif
        @if($off > 0)
            <td style="width: {{ $off }}mm; background-color: #eeeeee"></td>
        @endif
    </tr>
</table>
