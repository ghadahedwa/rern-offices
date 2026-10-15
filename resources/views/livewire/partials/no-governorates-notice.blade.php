{{--
    تنبيه «حسابك غير مربوط بأي محافظة» — للمستخدم غير super-admin بلا محافظات.
    ⚠️ القوائم والتقارير تظهر له فارغة عمداً (`[]` = لا رؤية، لا «بلا حدّ»)، وبلا هذا السطر
       يقرأ الشاشة الفارغة عطلاً. يُوضع أول الصفحة في كل شاشة مقيَّدة بالمحافظات.
--}}
@php $noticeUser = auth()->user(); @endphp
@if($noticeUser && ! $noticeUser->hasRole('super-admin') && $noticeUser->governorates()->doesntExist())
<div class="flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div class="text-sm">
        <p class="font-semibold text-amber-800 dark:text-amber-300">{{ __('home.no_governorates_title') }}</p>
        <p class="text-amber-700 dark:text-amber-400 mt-0.5">{{ __('home.no_governorates_body') }}</p>
    </div>
</div>
@endif
