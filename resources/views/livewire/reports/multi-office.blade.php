<div class="max-w-6xl mx-auto p-6 space-y-6">

    @php
        $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
        $lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';

        // كلاس الـ single-select — لون نص كامل دائماً (واضح، غير باهت) لتوحيد المظهر
        $selBase = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-[#c9a847]';
        $selText = fn ($v) => 'text-zinc-900 dark:text-zinc-100';

        // خيارات enum للأنظمة التقنية والتقييم
        $availabilityOpts = ['available' => __('home.option_available'), 'not_available' => __('home.option_not_available')];
        $queueOpts        = ['working' => __('home.option_working'), 'not_working' => __('home.option_not_working'), 'not_available' => __('home.option_not_available')];
        $camerasOpts      = ['available' => __('home.option_available'), 'not_available' => __('home.option_not_available'), 'broken' => __('home.option_broken')];
        $meterTypeOpts    = ['prepaid' => __('home.meter_type_prepaid'), 'invoice' => __('home.meter_type_invoice'), 'entity_meter' => __('home.meter_type_entity')];
        $debtOpts         = ['yes' => __('home.option_yes'), 'no' => __('home.option_no')];
        $workingDaysOpts  = [
            'full_week'  => __('home.working_days_full_week'),
            'one_day'    => __('home.working_days_one_day'),
            'two_days'   => __('home.working_days_two_days'),
            'three_days' => __('home.working_days_three_days'),
            'four_days'  => __('home.working_days_four_days'),
            'five_days'  => __('home.working_days_five_days'),
        ];
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.report_multi_title') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.report_multi_hint') }}</p>
            </div>
        </div>
        <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            {{ __('home.report_reset_filters') }}
        </button>
    </div>

    {{-- ── الفلاتر الأساسية ── --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_basic_filters') }}</h3>
        </div>

        {{-- بحث المحكمة مع autocomplete — معلّق مؤقتاً لحين تقييم فائدته (حقل نص حر).
             لإعادة التفعيل: ألغِ تعليق الكتلة. الكود الخلفي (الفلتر + الاقتراحات) باقٍ وخامل.
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @include('livewire.reports.includes.autocomplete-input', ['field' => 'districtCourt', 'label' => __('home.district_court'), 'suggestions' => $districtSuggestions])
        </div>
        --}}

        {{-- الصف الأول: المحافظة والمقر --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'governorateIds', 'options' => $governorates->pluck('name', 'id')->all(), 'label' => __('home.governorate_name'), 'live' => true])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'officeIds', 'options' => $officeOptions->pluck('name', 'id')->all(), 'label' => __('home.report_office_multi'), 'wireKey' => 'officeIds-' . md5($officeOptions->pluck('id')->implode(','))])
        </div>

        {{-- باقي الفلاتر الأساسية: 4 في الصف --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            @include('livewire.reports.includes.checkbox-group', ['field' => 'typeIds', 'options' => $officeTypes->pluck('name', 'id')->all(), 'label' => __('home.office_type')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'locationIds', 'options' => $locations->pluck('name', 'id')->all(), 'label' => __('home.location_description')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'workSystemIds', 'options' => $workSystems->pluck('name', 'id')->all(), 'label' => __('home.work_system')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'workingHoursIds', 'options' => $workingHoursOptions->pluck('name', 'id')->all(), 'label' => __('home.working_hours')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'contractualStatusIds', 'options' => $contractualStatuses->pluck('name', 'id')->all(), 'label' => __('home.contractual_status')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'structuralConditionIds', 'options' => $structuralConditions->pluck('name', 'id')->all(), 'label' => __('home.structural_condition')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'connectionTypeIds', 'options' => $connections->pluck('name', 'id')->all(), 'label' => __('home.connection_type')])
            @include('livewire.reports.includes.checkbox-group', ['field' => 'workingDays', 'options' => $workingDaysOpts, 'label' => __('home.working_days')])
        </div>

        {{-- نطاقات التواريخ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="{{ $lbl }}">{{ __('home.established_at') }}</label>
                <div class="flex items-center gap-2">
                    <input wire:model="establishedFrom" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_from') }}" />
                    <span class="text-xs text-zinc-400 shrink-0">{{ __('home.report_to') }}</span>
                    <input wire:model="establishedTo" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_to') }}" />
                </div>
            </div>
            <div>
                <label class="{{ $lbl }}">{{ __('home.mechanization_at') }}</label>
                <div class="flex items-center gap-2">
                    <input wire:model="mechanizationFrom" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_from') }}" />
                    <span class="text-xs text-zinc-400 shrink-0">{{ __('home.report_to') }}</span>
                    <input wire:model="mechanizationTo" type="date" class="{{ $inp }}" aria-label="{{ __('home.report_to') }}" />
                </div>
            </div>
        </div>

        {{-- زر فلاتر المتابعة --}}
        <div class="pt-1">
            <button type="button" wire:click="$toggle('showAdvanced')"
                    class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border border-[#c9a847] text-[#c9a847] hover:bg-[#c9a847]/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform {{ $showAdvanced ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
                {{ $showAdvanced ? __('home.report_hide_advanced') : __('home.report_show_advanced') }}
            </button>
        </div>
    </div>

    {{-- ── الفلاتر المتقدمة (نظام المتابعة) ── --}}
    @if($showAdvanced)
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-6 space-y-8">

        {{-- المجموعة 3+4: الخدمات والتجهيزات والأنظمة التقنية --}}
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_group_services_tech') }}</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                @include('livewire.reports.includes.checkbox-group', ['field' => 'microfilmIds', 'options' => $microfilmOptions->pluck('name', 'id')->all(), 'label' => __('home.microfilm_option')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'disabilitiesAccessIds', 'options' => $disabilitiesAccess->pluck('name', 'id')->all(), 'label' => __('home.disabilities_access_label')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'fireSafetyIds', 'options' => $fireSafetyOptions->pluck('name', 'id')->all(), 'label' => __('home.fire_safety_label')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'photocopyingIds', 'options' => $photocopyingOptions->pluck('name', 'id')->all(), 'label' => __('home.document_photocopying_service')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'buffetIds', 'options' => $buffetOptions->pluck('name', 'id')->all(), 'label' => __('home.buffet_service')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'cleanlinessContractIds', 'options' => $cleanlinessContracts->pluck('name', 'id')->all(), 'label' => __('home.cleanliness_contract')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'queueSystem', 'options' => $queueOpts, 'label' => __('home.queue_management_system')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'cameras', 'options' => $camerasOpts, 'label' => __('home.surveillance_cameras')])
                {{-- صف العدادات: كهرباء (نوع + مديونية) ثم مياه (نوع + مديونية) --}}
                @include('livewire.reports.includes.checkbox-group', ['field' => 'electricityMeterType', 'options' => $meterTypeOpts, 'label' => __('home.electricity_meter_type')])
                <div>
                    <label class="{{ $lbl }}">{{ __('home.electricity_meter_debt') }}</label>
                    <select wire:model="electricityMeterDebt" class="{{ $selBase }} {{ $selText($electricityMeterDebt) }}">
                        <option value="">{{ __('home.report_select_hint') }}</option>
                        @foreach ($debtOpts as $val => $text)<option value="{{ $val }}">{{ $text }}</option>@endforeach
                    </select>
                </div>
                @include('livewire.reports.includes.checkbox-group', ['field' => 'waterMeterType', 'options' => $meterTypeOpts, 'label' => __('home.water_meter_type')])
                <div>
                    <label class="{{ $lbl }}">{{ __('home.water_meter_debt') }}</label>
                    <select wire:model="waterMeterDebt" class="{{ $selBase }} {{ $selText($waterMeterDebt) }}">
                        <option value="">{{ __('home.report_select_hint') }}</option>
                        @foreach ($debtOpts as $val => $text)<option value="{{ $val }}">{{ $text }}</option>@endforeach
                    </select>
                </div>

                {{-- جهاز برايل في الصف التالي --}}
                <div>
                    <label class="{{ $lbl }}">{{ __('home.braille_sign_device') }}</label>
                    <select wire:model="braille" class="{{ $selBase }} {{ $selText($braille) }}">
                        <option value="">{{ __('home.report_select_hint') }}</option>
                        @foreach ($availabilityOpts as $val => $text)<option value="{{ $val }}">{{ $text }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- المجموعة 5: التقييم والجودة --}}
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_group_assessment') }}</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                @include('livewire.reports.includes.checkbox-group', ['field' => 'cleanlinessRating', 'options' => \App\Models\Office::CLEANLINESS_RATINGS, 'label' => __('home.cleanliness_rating')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'archiveRating', 'options' => \App\Models\Office::ARCHIVE_RATINGS, 'label' => __('home.archive_rating')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'scheduleCommitment', 'options' => \App\Models\Office::COMMITMENT_RATINGS, 'label' => __('home.work_schedule_commitment')])
                @include('livewire.reports.includes.checkbox-group', ['field' => 'citizenCommitment', 'options' => \App\Models\Office::COMMITMENT_RATINGS, 'label' => __('home.citizen_treatment_commitment')])
            </div>
        </div>

        {{-- المجموعة 6: المتابعة والزيارات --}}
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.report_group_followup') }}</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-end">
                <label class="flex items-center gap-2 px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 cursor-pointer text-sm text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model="neverVisited"
                           class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                    <span>{{ __('home.report_never_visited') }}</span>
                </label>
                <div>
                    <label class="{{ $lbl }}">{{ __('home.report_not_visited_months') }}</label>
                    <input wire:model="notVisitedMonths" type="number" min="1" placeholder="6" class="{{ $inp }}" />
                </div>
            </div>
        </div>

    </div>
    @endif

    {{-- ── شريط البحث ── --}}
    <div class="flex items-center justify-center gap-3">
        <button type="button" wire:click="search"
                class="inline-flex items-center justify-center gap-2 px-8 py-2.5 rounded-lg bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-semibold transition shadow-sm">
            <svg wire:loading.remove wire:target="search" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ __('home.report_search_btn') }}
        </button>
    </div>

    {{-- ── النتائج ── --}}
    @if($hasSearched)
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="font-medium">{{ __('home.report_results_count') }}:</span>
                <span class="px-2.5 py-0.5 rounded-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847] font-semibold">
                    {{ $offices->total() }} {{ __('home.report_office_unit') }}
                </span>
            </div>
        </div>

        @if($offices->total() > 0)
        @php
            $excelBtn = 'inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-900/20 transition disabled:opacity-50';
            $pdfBtn   = 'inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20 transition disabled:opacity-50';
            $excelSvg = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
        @endphp

        {{-- ── التقريران: شامل / مخصّص ── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm divide-y divide-zinc-100 dark:divide-zinc-700">

            {{-- تقرير شامل --}}
            <div class="p-5 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.report_comprehensive_title') }}</h3>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ __('home.report_comprehensive_hint') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="{{ $excelBtn }}">
                        {!! $excelSvg !!}
                        {{ __('home.report_export_excel') }}
                    </button>
                    <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf" class="{{ $pdfBtn }}">
                        {!! $excelSvg !!}
                        {{ __('home.report_export_pdf') }}
                    </button>
                </div>
            </div>

            {{-- تقرير مخصّص --}}
            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('home.report_custom_title') }}</h3>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ __('home.report_custom_hint') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="exportCustomExcel" wire:loading.attr="disabled" wire:target="exportCustomExcel" class="{{ $excelBtn }}">
                            {!! $excelSvg !!}
                            {{ __('home.report_export_excel') }}
                        </button>
                        <button type="button" wire:click="exportCustomPdf" wire:loading.attr="disabled" wire:target="exportCustomPdf" class="{{ $pdfBtn }}">
                            {!! $excelSvg !!}
                            {{ __('home.report_export_pdf') }}
                        </button>
                    </div>
                </div>

                {{-- منتقي الأعمدة (الثابتة + الفلاتر المستخدَمة فقط) --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4" x-data>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('home.report_custom_columns') }}</p>
                        {{-- عدّاد حيّ بعدد الأعمدة المختارة (أحمر إذا تعدّى حد الـ PDF) --}}
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full transition"
                              :class="$wire.selectedColumns.length > 12
                                  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                  : 'bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]'">
                            <span x-text="$wire.selectedColumns.length"></span> {{ __('home.report_column_unit') }}
                        </span>
                    </div>
                    <p class="text-[11px] text-red-600 dark:text-red-400 mb-3" x-cloak x-show="$wire.selectedColumns.length > 12">
                        {{ __('home.report_custom_pdf_note', ['max' => 12]) }}
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                        @foreach($customColumnGroups as $group => $cols)
                        <div>
                            <p class="text-[11px] font-semibold text-[#b8962e] dark:text-[#c9a847] mb-1.5">{{ $group }}</p>
                            <div class="space-y-1">
                                @foreach($cols as $col)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200 {{ $col['fixed'] ? 'opacity-70' : 'cursor-pointer' }}"
                                       @if($col['fixed']) title="{{ __('home.report_custom_column_locked') }}" @endif>
                                    <input type="checkbox" value="{{ $col['key'] }}" wire:model="selectedColumns"
                                           @disabled($col['fixed'])
                                           class="rounded border-zinc-300 dark:border-zinc-600 text-[#c9a847] focus:ring-[#c9a847]" />
                                    <span>{{ $col['label'] }}</span>
                                    @if($col['fixed'])<span class="text-[10px] text-zinc-400">🔒</span>@endif
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full text-sm text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.office_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.governorate_name') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.office_type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.connection_type') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.structural_condition') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('home.report_last_visit') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($offices as $office)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-3 text-zinc-500">{{ $offices->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $office->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $office->governorate->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]">
                                    {{ $office->officeType->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $office->connectionType->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $office->structuralCondition->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $office->visited_at?->format('Y-m-d') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-400">{{ __('home.no_offices') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $offices->links() }}</div>
    </div>
    @else
    {{-- لم يُبحث بعد --}}
    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 p-12 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('home.report_search_prompt') }}</p>
    </div>
    @endif

    {{-- keepalive: يجدد الـ snapshot والـ CSRF كل 10 دقائق --}}
    <div x-data x-init="setInterval(() => $wire.$refresh(), 600000)" class="hidden"></div>
</div>
