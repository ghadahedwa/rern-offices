{{-- ── الدور والنطاق ──
     صف الدور وحده، ويليه صف النطاق الذي تستلزمه صلاحياته:
       عنوان «إدارة المقرات» → المحافظات
       عنوان «المراسلات»     → الطرف + المسمّى الوظيفي في صف واحد
     المصدر الواحد للتجميع: App\Support\PermissionGroups (نفسه في شبكة الأدوار) --}}

{{-- الدور — صف وحده --}}
<div class="flex flex-col gap-1">
    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ __('home.role') }} <span class="text-red-500">*</span>
    </label>
    <select wire:model.live="role"
            class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
        <option value="">-- {{ __('home.select_role') }} --</option>
        @foreach($roles as $r)
            <option value="{{ $r->name }}">{{ $r->name }}</option>
        @endforeach
    </select>
    @error('role') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    @if($role && ! $needsGovernorates && ! $needsEntity)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.user_scope_none_hint') }}</p>
    @endif
</div>

{{-- نطاق المقرات: المحافظات --}}
@if($needsGovernorates)
    <div class="flex flex-col gap-3 rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/5 dark:bg-[#c9a847]/10 p-4">
        <div>
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('home.governorates') }}</label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('home.user_governorates_hint') }}</p>
        </div>
        @if($governorates->isEmpty())
            <p class="text-xs text-zinc-400">{{ __('home.no_governorates') }}</p>
        @else
            <div class="grid grid-cols-2 gap-2">
                @foreach($governorates as $governorate)
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition">
                        <input type="checkbox" wire:model="selectedGovernorates"
                               value="{{ $governorate->id }}"
                               class="w-4 h-4 rounded accent-[#c9a847]" />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $governorate->name }}</span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- نطاق المراسلات: الطرف + المسمّى الوظيفي في صف واحد --}}
@if($needsEntity)
    <div class="rounded-xl border border-[#c9a847]/40 bg-[#c9a847]/5 dark:bg-[#c9a847]/10 p-4 space-y-3">
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.user_entity_hint') }}</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.corr_entity_name') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model="correspondence_entity_id"
                        class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]">
                    <option value="">-- {{ __('home.corr_entity_select') }} --</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                    @endforeach
                </select>
                @error('correspondence_entity_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('home.user_job_title') }}
                </label>
                <input type="text" wire:model="job_title" autocomplete="off"
                       placeholder="{{ __('home.user_job_title_placeholder') }}"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm w-full bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]" />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.user_job_title_hint') }}</p>
                @error('job_title') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
@endif
