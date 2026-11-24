<x-layouts::auth :title="__('تسجيل الدخول')">
    @if(\App\Support\SystemFreeze::isActive())
        {{-- النظام مجمَّد: شاشة الصيانة، ودخول مدير النظام مطويّ في ذيلها --}}
        @include('livewire.auth.partials.frozen-card', ['withLoginForm' => true])
    @else
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full">

        {{-- Card heading --}}
        <div class="flex items-center justify-center gap-3 mb-6">
            <span class="text-xl font-light" style="color: #c9a847;">—</span>
            <h2 class="text-xl font-bold" style="color: #2c3e6b;">تسجيل الدخول</h2>
            <span class="text-xl font-light" style="color: #c9a847;">—</span>
        </div>

        {{-- Session Status --}}
        <x-auth-session-status class="text-center mb-4" :status="session('status')" />

        @include('livewire.auth.partials.login-form')
    </div>
    @endif
</x-layouts::auth>
