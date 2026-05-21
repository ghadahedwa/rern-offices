<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('offices.index') }}" wire:navigate
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                {{ $isEditing ? __('home.edit_office') : __('home.add_office') }}
            </h1>
        </div>

        @if($isEditing)
        <a href="{{ route('offices.statistics', $office_id) }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            احصائيات المقر
        </a>
        @endif
    </div>

    {{-- Step Progress --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => __('home.step_1_label'),
                    2 => __('home.step_2_label'),
                    3 => __('home.step_3_label'),
                    4 => 'الإحصائيات',
                    5 => __('home.step_4_label'),
                ];
            @endphp
            @foreach ($steps as $num => $label)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-1">
                        @if($office_id && $num !== $step)
                        <button type="button" wire:click="goToStep({{ $num }})"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all cursor-pointer
                                    {{ $step > $num ? 'bg-[#c9a847] text-white hover:bg-[#b8962e]' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}">
                            @if ($step > $num)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </button>
                        @else
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
                                {{ $step === $num ? 'bg-[#c9a847] text-white ring-4 ring-[#c9a847]/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 opacity-50' }}">
                            @if ($step > $num)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        @endif
                        <span class="text-xs font-medium hidden sm:block {{ $step === $num ? 'text-[#c9a847]' : 'text-zinc-400' }}">
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

        @php
            $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
            $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
            $err = 'text-red-500 text-xs mt-1';
        @endphp

        {{-- ===================== STEP 1 ===================== --}}
        @if ($step === 1)
        @include('livewire.offices.includes.create-step1')
        @endif

        {{-- ===================== STEP 2 ===================== --}}
        @if ($step === 2)
            @include('livewire.offices.includes.create-step2')
        @endif

        {{-- ===================== STEP 3 ===================== --}}
        @if ($step === 3)
            @include('livewire.offices.includes.create-step3') 
        @endif

        {{-- ===================== STEP 4 ===================== --}}
        @if ($step === 4)
            @include('livewire.offices.includes.create-step4')
        @endif

        {{-- ===================== STEP 5 ===================== --}}
        @if ($step === 5)
            @include('livewire.offices.includes.create-step5')
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
                    حفظ والسابق
                </button>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($step === 1)
                <button wire:click="saveAndExit" type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 text-sm font-medium transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('home.save_and_exit') }}
                </button>
            @endif
            @if ($step < $totalSteps)
                <button wire:click="nextStep" type="button"
                        class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                    {{ __('home.next_step') }}
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
                    {{ __('home.save_office_btn') }}
                </button>
            @endif
        </div>
    </div>

</div>
