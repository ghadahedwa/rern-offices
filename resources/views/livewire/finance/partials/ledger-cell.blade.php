{{-- خلية اتصالات في «الإجمالي العام»: رصيد آخر الشهر، وتحته + الإيراد و− المنصرف (الشكل «ب»).
     يتطلب: $c [balance, income, expense] --}}
<span class="block font-semibold text-zinc-800 dark:text-zinc-100" dir="ltr">{{ \App\Support\Finance\Money::format($c['balance']) }}</span>
<span class="block text-[11px] font-normal text-emerald-700 dark:text-emerald-400" dir="ltr">+ {{ \App\Support\Finance\Money::format($c['income']) }}</span>
<span class="block text-[11px] font-normal text-red-600 dark:text-red-400" dir="ltr">− {{ \App\Support\Finance\Money::format($c['expense']) }}</span>
