<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('offices.index') }}" wire:navigate
           class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.add_office') }}</h1>
    </div>

    {{-- Step Progress --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => 'المعلومات الأساسية',
                    2 => 'تشكيل المكتب',
                    3 => 'التقييمات',
                    4 => 'الوسائط والملاحظات',
                ];
            @endphp
            @foreach ($steps as $num => $label)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
                            {{ $step > $num ? 'bg-[#c9a847] text-white' : ($step === $num ? 'bg-[#c9a847] text-white ring-4 ring-[#c9a847]/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500') }}">
                            @if ($step > $num)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="text-xs font-medium hidden sm:block
                            {{ $step === $num ? 'text-[#c9a847]' : 'text-zinc-400' }}">
                            {{ $label }}
                        </span>
                    </div>
                    @if (!$loop->last)
                        <div class="flex-1 h-0.5 mx-3 {{ $step > $num ? 'bg-[#c9a847]' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step Content --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">

        {{-- ===================== STEP 1 ===================== --}}
        @if ($step === 1)
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-6 pb-3 border-b border-zinc-100 dark:border-zinc-700">
                المعلومات الأساسية
            </h2>

            <div class="space-y-5">

                

                {{-- Supervising user + Parent office --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Governorate --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    	{{ __('home.governorate') }}<span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="governorate_id"
                            class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                        <option value="">— اختر المحافظة —</option>
                        @foreach ($governorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                        @endforeach
                    </select>
                    @error('governorate_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('home.supervising_counselor') }}</label>
                        @php
                            $counselor = $governorate_id
                                ? ($governorates->firstWhere('id', $governorate_id)?->supervising_counselor ?? '—')
                                : '—';
                        @endphp
                        <div class="w-full border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400">
                            {{ $counselor }}
                        </div>
                    </div>
                </div>

                {{-- Name + Established date --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            اسم المقر <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name" type="text" placeholder="اسم المقر"
                               class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">تاريخ الإنشاء</label>
                        <input wire:model="established_at" type="date"
                               class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    </div>
                </div>

                {{-- Type + Location description --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            نوع المقر <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="type"
                                class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                            <option value="">— اختر نوع المقر —</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">وصف المقر</label>
                        <select wire:model="location_description"
                                class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                            <option value="">— اختر وصف المقر —</option>
                            @foreach ($locations as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Work system + Connection type --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            نظام العمل <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="work_system"
                                class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                            <option value="">— اختر نظام العمل —</option>
                            @foreach ($workSystems as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('work_system') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">نوع خط الربط</label>
                        <select wire:model="connection_type"
                                class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                            <option value="">— اختياري —</option>
                            @foreach ($connections as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">العنوان تفصيلاً</label>
                    <textarea wire:model="address" rows="2" placeholder="العنوان الكامل للمقر"
                              class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847] resize-none"></textarea>
                </div>

                {{-- Google maps + Floors --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">رابط جوجل مابس</label>
                        <input wire:model="google_maps_link" type="url" placeholder="https://maps.google.com/..."
                               class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">تحديد أدوار المقر</label>
                        <input wire:model="floors_description" type="text" placeholder="مثال: الدور الأول والثاني"
                               class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                    </div>
                </div>

            </div>
        @endif

        {{-- ===================== STEP 2 ===================== --}}
        @if ($step === 2)
            <div class="flex flex-col items-center justify-center py-16 text-center gap-3">
                <div class="w-14 h-14 rounded-full bg-[#c9a847]/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300">تشكيل المكتب</h3>
                <p class="text-sm text-zinc-400">قيد التطوير — سيتم إضافته قريباً</p>
            </div>
        @endif

        {{-- ===================== STEP 3 ===================== --}}
        @if ($step === 3)
            <div class="flex flex-col items-center justify-center py-16 text-center gap-3">
                <div class="w-14 h-14 rounded-full bg-[#c9a847]/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300">التقييمات والامتثال</h3>
                <p class="text-sm text-zinc-400">قيد التطوير — سيتم إضافته قريباً</p>
            </div>
        @endif

        {{-- ===================== STEP 4 ===================== --}}
        @if ($step === 4)
            <div class="flex flex-col items-center justify-center py-16 text-center gap-3">
                <div class="w-14 h-14 rounded-full bg-[#c9a847]/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300">الوسائط والملاحظات</h3>
                <p class="text-sm text-zinc-400">قيد التطوير — سيتم إضافته قريباً</p>
            </div>
        @endif

    </div>

    {{-- Navigation Buttons --}}
    <div class="flex items-center justify-between">
        <div>
            @if ($step > 1)
                <button wire:click="prevStep" type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    السابق
                </button>
            @endif
        </div>

        <div>
            @if ($step < $totalSteps)
                <button wire:click="nextStep" type="button"
                        class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                    التالي
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <button wire:click="save" type="button"
                        class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    حفظ المقر
                </button>
            @endif
        </div>
    </div>

</div>
