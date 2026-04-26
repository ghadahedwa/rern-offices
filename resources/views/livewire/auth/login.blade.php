<x-layouts::auth :title="__('تسجيل الدخول')">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full">

        {{-- Card heading --}}
        <div class="flex items-center justify-center gap-3 mb-6">
            <span class="text-xl font-light" style="color: #c9a847;">—</span>
            <h2 class="text-xl font-bold" style="color: #2c3e6b;">تسجيل الدخول</h2>
            <span class="text-xl font-light" style="color: #c9a847;">—</span>
        </div>

        {{-- Session Status --}}
        <x-auth-session-status class="text-center mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            {{-- Username --}}
            <div class="relative">
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="اسم المستخدم"
                    class="w-full border border-gray-300 rounded-lg py-3 pr-4 pl-10 text-right text-gray-800 bg-white transition @error('username') border-red-400 @enderror"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                @error('username')
                    <p class="text-red-500 text-xs mt-1 text-right">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="كلمة المرور"
                    class="w-full border border-gray-300 rounded-lg py-3 pr-4 pl-10 text-right text-gray-800 bg-white transition @error('password') border-red-400 @enderror"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                @error('password')
                    <p class="text-red-500 text-xs mt-1 text-right">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember me --}}
            <div class="flex items-center justify-end gap-2">
                <label for="remember" class="text-sm text-gray-600 cursor-pointer">تذكرني</label>
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    {{ old('remember') ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-gray-300 cursor-pointer"
                    style="accent-color: #c9a847;"
                />
            </div>

            {{-- Submit button --}}
            <button
                type="submit"
                data-test="login-button"
                class="w-full text-white font-semibold py-3 rounded-lg flex items-center justify-center gap-2 transition hover:opacity-90 active:scale-[0.99]"
                style="background-color: #c9a847;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                تسجيل الدخول
            </button>
        </form>
    </div>
</x-layouts::auth>
