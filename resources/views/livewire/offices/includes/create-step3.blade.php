
            {{-- ── Section: بيانات الزيارة والتقييم ── --}}
            <div class="mb-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.step_3_label') }}</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- تاريخ الزيارة --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.visited_at') }}</label>
                        <input type="date" wire:model="visited_at" class="{{ $inp }}" />
                        @error('visited_at') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

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

                    {{-- كاميرات المراقبة --}}
                    <div>
                        <label class="{{ $lbl }}">{{ __('home.surveillance_cameras') }}</label>
                        <select wire:model="surveillance_cameras" class="{{ $inp }}">
                            <option value="">{{ __('home.select_option') }}</option>
                            <option value="available">{{ __('home.option_available') }}</option>
                            <option value="not_available">{{ __('home.option_not_available') }}</option>
                            <option value="broken">{{ __('home.option_broken') }}</option>
                        </select>
                    </div>

                </div>

                {{-- احتياجات المقر --}}
                <div class="mt-5">
                    <label class="{{ $lbl }}">احتياجات المقر</label>
                    <textarea wire:model="office_needs" rows="4"
                              class="{{ $inp }} resize-none"
                              placeholder="احتياجات المقر..."></textarea>
                </div>
                
                {{-- السلبيات ومقترحات الحل --}}
                <div class="mt-5">
                    <label class="{{ $lbl }}">{{ __('home.negatives_and_solutions') }}</label>
                    <textarea wire:model="negatives_and_solutions" rows="4"
                              class="{{ $inp }} resize-none"
                              placeholder="{{ __('home.negatives_and_solutions') }}..."></textarea>
                    @error('negatives_and_solutions') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                {{-- مقترحات التطوير --}}
                <div class="mt-5">
                    <label class="{{ $lbl }}">{{ __('home.development_proposals') }}</label>
                    <textarea wire:model="development_proposals" rows="4"
                              class="{{ $inp }} resize-none"
                              placeholder="{{ __('home.development_proposals') }}..."></textarea>
                    @error('development_proposals') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>


            </div>


