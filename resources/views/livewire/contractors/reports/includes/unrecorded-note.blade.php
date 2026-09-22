{{--
    تنبيه «لم تُرصد أيام حضورهم» — **يظهر حين يقع وحده**.

    ⚠️ بديلٌ عن عمودٍ ثالث كان يكون **صفراً لكل الصفوف في الشهر العادي**: عمودٌ
       كهذا ضجيجٌ لا معلومة، ومصطلحُه («غير مراجَع») لا يعرفه مَن يقرأ التقرير.
    ⚠️ وهو ما يبقى من خانة «وصل الكشف» في شبكة التسجيل: بدونه تصير الخانة بلا
       أثرٍ في التقارير، وعاملٌ لم يصل كشفه يمرّ «حاضراً الشهر كله» بلا إشارة.
--}}
@if($count > 0)
    <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-2.5 flex items-start gap-2">
        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <p class="text-xs text-amber-800 dark:text-amber-300">
            {{ $count === 1 ? __('home.ct_rep_unrecorded_one') : __('home.ct_rep_unrecorded', ['count' => $count]) }}
        </p>
    </div>
@endif
