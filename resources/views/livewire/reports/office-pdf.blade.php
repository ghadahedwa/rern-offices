<div class="max-w-4xl mx-auto p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_office_pdf_title') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_office_pdf_desc') }}</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">

        <div class="flex items-center gap-3 mb-1">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">تحديد المقر</h3>
        </div>

        @php
            $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
            $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';
        @endphp

        {{-- Governorate --}}
        <div>
            <label class="{{ $lbl }}">المحافظة <span class="text-red-500">*</span></label>
            <select wire:model.live="selectedGovernorateId" class="{{ $inp }}">
                <option value="">— اختر المحافظة —</option>
                @foreach($governorates as $gov)
                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Office --}}
        <div>
            <label class="{{ $lbl }}">المقر <span class="text-red-500">*</span></label>
            <select wire:model.live="selectedOfficeId" class="{{ $inp }}"
                    @disabled(!$selectedGovernorateId)>
                <option value="">
                    {{ $selectedGovernorateId ? '— اختر المقر —' : '— اختر المحافظة أولاً —' }}
                </option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                @endforeach
            </select>
            @if($selectedGovernorateId && $offices->isEmpty())
            <p class="text-xs text-zinc-400 mt-1.5">لا توجد مقرات في هذه المحافظة</p>
            @endif
        </div>

        {{-- Button --}}
        @if($selectedOfficeId)
        <button type="button" wire:click="generatePdf"
                class="flex items-center justify-center gap-2 w-full py-3 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-semibold transition cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ __('home.generate_pdf') }}
        </button>
        @else
        <button type="button" disabled
                class="flex items-center justify-center gap-2 w-full py-3 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 text-sm font-semibold cursor-not-allowed">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ __('home.generate_pdf') }}
        </button>
        @endif

    </div>

</div>
