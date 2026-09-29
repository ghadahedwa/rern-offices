{{--
    منتقي مرفقات مستند المخازن — يقرأ `$attachments` من الـtrait `CollectsAttachments`.
    المعاملات: $limit (كم ملفاً يقبل بعد) · $label (اختياري) · $required (نجمة الإلزام) · $hint (سطر إرشادي اختياري)
--}}
@php
    $count = count($attachments);
@endphp
{{-- أحداث الرفع تنطلق من حقل الملف وتصعد إلى هنا. الملفات المختارة معاً تُرفع في طلبٍ واحد،
     فالنسبة للدفعة كلها لا لكل ملف. ⚠️ لا x-data على الحقل نفسه — يكسر wire:model --}}
<div class="flex flex-col gap-2"
     x-data="{ uploading: false, progress: 0, failed: false }"
     x-on:livewire-upload-start="uploading = true; progress = 0; failed = false"
     x-on:livewire-upload-progress="progress = $event.detail.progress"
     x-on:livewire-upload-finish="uploading = false"
     x-on:livewire-upload-error="uploading = false; failed = true"
     x-on:livewire-upload-cancel="uploading = false">
    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ $label ?? __('home.wh_attachments') }}
        @if($required ?? false) <span class="text-red-500">*</span> @endif
        <span class="text-xs font-normal text-zinc-400">({{ __('home.wh_attachments_count', ['count' => $count, 'max' => $limit]) }})</span>
    </label>

    {{-- ⚠️ القائمة والحقل في الصفحة دائماً (يُخفيان ولا يُزالان) وبمفتاحٍ ثابت: إشارة «انتهى الرفع»
         يُطلقها Livewire من الحقل **بعد** إعادة الرسم، فلو استبدلت إعادةُ الرسم الحقلَ — وهو ما يقع حين
         تظهر القائمة فوقه أول مرة — خرجت الإشارة من حقلٍ مفصول لا تصعد منه، فبقي الشريط يدور وزرّ الحفظ
         مقفولاً للأبد. --}}
    <ul wire:key="attachments-list" @if(! $count) style="display:none" @endif
        class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
        @foreach($attachments as $i => $file)
            <li wire:key="att-{{ $i }}-{{ $file->getFilename() }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                <span class="flex items-center gap-2 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-[#b8962e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="truncate text-zinc-700 dark:text-zinc-200" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</span>
                    <span class="shrink-0 text-xs text-zinc-400" dir="ltr">{{ number_format($file->getSize() / 1048576, 1) }} MB</span>
                </span>
                <button type="button" wire:click="removeAttachment({{ $i }})"
                        class="shrink-0 text-xs text-red-500 hover:text-red-600 font-medium transition">
                    {{ __('home.wh_attachments_remove') }}
                </button>
            </li>
        @endforeach
    </ul>

    <label wire:key="attachments-input" @if($count >= $limit) style="display:none" @endif
           class="self-start cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#c9a847]/10 text-[#b8962e] text-sm font-medium hover:bg-[#c9a847]/20 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        {{ $count ? __('home.wh_attachments_add_more') : __('home.wh_attachments_choose') }}
        <input type="file" class="hidden" multiple wire:model="pickedFiles" accept="image/jpeg,image/png,.jpg,.jpeg,.png,.pdf" />
    </label>

    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $hint ?? __('home.wh_attachments_hint', ['max' => \App\Models\WarehouseAttachment::MAX_PER_DOCUMENT]) }}</p>
    <div x-show="uploading" style="display:none" class="flex items-center gap-3">
        <div class="flex-1 h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
            <div class="h-full bg-[#c9a847] transition-all duration-200" :style="`width: ${progress}%`"></div>
        </div>
        <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ __('home.uploading') }} <span x-text="progress + '٪'" dir="ltr"></span></span>
    </div>
    <p x-show="failed" style="display:none" class="text-red-500 text-xs">{{ __('home.wh_attachments_upload_failed') }}</p>
    @error('attachments') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
    @error('attachments.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
</div>
