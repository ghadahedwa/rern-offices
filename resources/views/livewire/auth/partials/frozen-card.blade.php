{{-- كارت الصيانة وقت تجميد النظام (App\Support\SystemFreeze) — مكان كارت تسجيل الدخول.
     $withLoginForm: فورم دخول مدير النظام مطويّاً في ذيل الكارت (شاشة الدخول وحدها) —
     يُفتح وحده إن رجع بخطأ. <details> لا Alpine: صفحة الدخول بلا مكوّن Livewire. --}}
{{-- الصفحة بلا سكرول: فتح «دخول مدير النظام» يُخفي الشعار والأيقونة ليسع الفورم الشاشة --}}
<style>
    body:has(details.freeze-admin[open]) .auth-logo,
    body:has(details.freeze-admin[open]) .freeze-icon { display: none; }
</style>
<div class="bg-white rounded-2xl shadow-lg w-full overflow-hidden" data-test="system-frozen">
    <div class="h-1.5" style="background:#c9a847"></div>
    <div class="px-6 sm:px-8 py-6 text-center">
        <div class="freeze-icon mx-auto w-12 h-12 rounded-full flex items-center justify-center mb-3" style="background:#c9a8471f">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="#b8962e" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold mb-3" style="color:#2c3e6b;">{{ __('home.freeze_title') }}</h2>
        <p class="text-base sm:text-lg text-gray-500 mb-3">{{ __('home.freeze_refer_to') }}</p>
        <div class="inline-block rounded-xl px-6 py-3 border-2" style="border-color:#c9a847;background:#c9a84714">
            <p class="text-2xl sm:text-3xl font-bold" style="color:#2c3e6b;">{{ \App\Support\SystemFreeze::contact() }}</p>
        </div>
    </div>
    @if(!empty($withLoginForm))
        <details class="freeze-admin border-t border-gray-100" @if($errors->any()) open @endif>
            <summary class="py-3 text-center text-xs text-gray-400 underline underline-offset-4 decoration-dotted cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                {{ __('home.freeze_admin_login') }}
            </summary>
            <div class="px-6 sm:px-8 pb-5">
                @include('livewire.auth.partials.login-form')
            </div>
        </details>
    @endif
</div>
