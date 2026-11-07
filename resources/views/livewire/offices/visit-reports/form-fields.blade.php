
            {{-- بنود تقرير الزيارة — للمفتش والمستشار معاً (App\Support\OfficeVisitReports::FIELDS)
                 ثلاثة كروت: الزيارة (أكثر ما يُعدَّل — ٦٥٨ من ١٥٤٣ تعديلاً على السيرفر) · التقييم · الملاحظات --}}
            @php $card = 'bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 max-sm:p-4'; @endphp

            {{-- ── الزيارة: آخر زيارة محفوظة ومنذ متى + خانة التاريخ ── --}}
            <div class="rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/5 dark:bg-[#c9a847]/10 p-6 max-sm:p-4">
                <div class="flex max-sm:flex-col sm:items-end justify-between gap-4">
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400" style="font-size: .85rem">{{ __('home.vr_last_visit') }}</p>
                        @if($lastVisit)
                            <p class="font-semibold text-zinc-800 dark:text-zinc-100 mt-0.5" style="font-size: 1.35rem">{{ $lastVisit['date'] }}</p>
                            @if($lastVisit['since'])
                                <p class="mt-0.5 {{ $lastVisit['overdue'] ? 'text-amber-700 dark:text-amber-400' : 'text-zinc-500 dark:text-zinc-400' }}" style="font-size: .85rem">{{ $lastVisit['since'] }}</p>
                            @endif
                        @else
                            <p class="font-semibold text-zinc-400 dark:text-zinc-500 mt-0.5" style="font-size: 1.1rem">{{ __('home.vr_never_visited') }}</p>
                        @endif
                    </div>

                    <div class="flex items-end gap-2 max-sm:w-full">
                        <div class="max-sm:flex-1">
                            <label class="{{ $lbl }}">{{ __('home.vr_visit_date') }}</label>
                            <input type="date" wire:model="visited_at" class="{{ $inp }} sm:w-48" />
                        </div>
                        @if($canEditReport)
                            <button type="button" wire:click="visitToday"
                                    class="shrink-0 px-4 py-2 rounded-lg bg-white dark:bg-zinc-800 border border-[#c9a847] text-[#b8962e] hover:bg-[#c9a847]/10 font-semibold transition"
                                    style="font-size: .9rem">
                                {{ __('home.vr_visit_today') }}
                            </button>
                        @endif
                    </div>
                </div>
                @error('visited_at') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>

            {{-- ── التقييم ── --}}
            <div class="{{ $card }}">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ __('home.vr_section_ratings') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- الحالة الإنشائية --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.structural_condition') }}</label>
                        <select wire:model="structural_condition_id" class="{{ $inp }}">
                            <option value="">{{ __('home.select_structural') }}</option>
                            @foreach ($structuralConditions as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('structural_condition_id') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- تقييم نظافة المقر --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.cleanliness_rating') }}</label>
                        <select wire:model="cleanliness_rating" class="{{ $inp }}">
                            <option value="">— {{ __('home.cleanliness_rating') }} —</option>
                            @foreach (\App\Models\Office::CLEANLINESS_RATINGS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('cleanliness_rating') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- تقييم غرف الحفظ --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.archive_rating') }}</label>
                        <select wire:model="archive_rating" class="{{ $inp }}">
                            <option value="">— {{ __('home.archive_rating') }} —</option>
                            @foreach (\App\Models\Office::ARCHIVE_RATINGS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('archive_rating') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- مدى الالتزام بمواعيد العمل --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.work_schedule_commitment') }}</label>
                        <select wire:model="work_schedule_commitment" class="{{ $inp }}">
                            <option value="">— {{ __('home.work_schedule_commitment') }} —</option>
                            @foreach (\App\Models\Office::COMMITMENT_RATINGS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('work_schedule_commitment') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- مدى الالتزام بحسن معاملة المواطنين --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.citizen_treatment_commitment') }}</label>
                        <select wire:model="citizen_treatment_commitment" class="{{ $inp }}">
                            <option value="">— {{ __('home.citizen_treatment_commitment') }} —</option>
                            @foreach (\App\Models\Office::COMMITMENT_RATINGS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('citizen_treatment_commitment') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- ── الملاحظات ── --}}
            <div class="{{ $card }} space-y-5">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ __('home.vr_section_notes') }}</h3>
                </div>

                {{-- احتياجات المقر --}}
                <div>
                    <label class="{{ $lbl }}">{{ __('home.office_needs') }}</label>
                    <textarea wire:model="office_needs" rows="4"
                              class="{{ $inp }} resize-none"
                              placeholder="{{ __('home.office_needs') }}..."></textarea>
                    @error('office_needs') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                {{-- السلبيات ومقترحات الحل --}}
                <div>
                    <label class="{{ $lbl }}">{{ __('home.negatives_and_solutions') }}</label>
                    <textarea wire:model="negatives_and_solutions" rows="4"
                              class="{{ $inp }} resize-none"
                              placeholder="{{ __('home.negatives_and_solutions') }}..."></textarea>
                    @error('negatives_and_solutions') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                {{-- مقترحات التطوير --}}
                <div>
                    <label class="{{ $lbl }}">{{ __('home.development_proposals') }}</label>
                    <textarea wire:model="development_proposals" rows="4"
                              class="{{ $inp }} resize-none"
                              placeholder="{{ __('home.development_proposals') }}..."></textarea>
                    @error('development_proposals') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>
            </div>
