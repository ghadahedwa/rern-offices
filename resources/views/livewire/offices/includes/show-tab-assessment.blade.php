
            @php
            $cameraLabels = [
                'available'     => __('home.option_available'),
                'not_available' => __('home.option_not_available'),
                'broken'        => __('home.option_broken'),
            ];
            @endphp

            {{-- بيانات الزيارة والتقييم --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.step_3_label') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.visited_at') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->visited_at?->format('Y-m-d') ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.structural_condition') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $office->structuralCondition->name ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.cleanliness_rating') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ \App\Models\Office::CLEANLINESS_RATINGS[$office->cleanliness_rating] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.archive_rating') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ \App\Models\Office::ARCHIVE_RATINGS[$office->archive_rating] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.work_schedule_commitment') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->work_schedule_commitment] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.citizen_treatment_commitment') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ \App\Models\Office::COMMITMENT_RATINGS[$office->citizen_treatment_commitment] ?? __('home.no_data') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">{{ __('home.surveillance_cameras') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $cameraLabels[$office->surveillance_cameras] ?? __('home.no_data') }}</p>
                    </div>
                </div>
            </div>

            @if($office->office_needs || $office->negatives_and_solutions || $office->development_proposals)
            <div class="border-t border-zinc-100 dark:border-zinc-700"></div>
            <div class="space-y-4">
                @if($office->office_needs)
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.office_needs') }}</p>
                    <p class="text-sm text-zinc-800 dark:text-zinc-100 whitespace-pre-wrap leading-relaxed">{{ $office->office_needs }}</p>
                </div>
                @endif
                @if($office->negatives_and_solutions)
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.negatives_and_solutions') }}</p>
                    <p class="text-sm text-zinc-800 dark:text-zinc-100 whitespace-pre-wrap leading-relaxed">{{ $office->negatives_and_solutions }}</p>
                </div>
                @endif
                @if($office->development_proposals)
                <div>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">{{ __('home.development_proposals') }}</p>
                    <p class="text-sm text-zinc-800 dark:text-zinc-100 whitespace-pre-wrap leading-relaxed">{{ $office->development_proposals }}</p>
                </div>
                @endif
            </div>
            @endif
