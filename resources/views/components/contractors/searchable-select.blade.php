{{--
    منسدلةٌ فوقها سطرُ بحث — للقوائم الطويلة (مقرات المحافظة · عاملوها).

    البحث **في السيرفر عبر `ArabicText`** لا في المتصفح: قاعدة البحث العربي في
    المشروع تُوحّد الألف والياء والتاء المربوطة، وتكرارها في JS يجعل ما يجده
    المستخدم هنا مخالفاً لما تجده بقية الشاشات.

    - :label · :required   عنوان الحقل وهل هو إلزامي
    - :search-model        اسم خاصية البحث   (wire:model.live)
    - :value-model         اسم خاصية القيمة  (wire:model)
    - :options             عناصر بـ id · label · title
    - :placeholder         نصّ الخيار الفارغ
--}}
@props([
    'label',
    'searchModel',
    'valueModel',
    'options',
    'placeholder' => '',
    'searchPlaceholder' => null,
    'required' => false,
])

@php
    $inp = 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]/40';
@endphp

<div>
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>

    <div class="space-y-1.5">
        <div class="relative">
            <svg class="w-3.5 h-3.5 absolute top-1/2 -translate-y-1/2 right-2.5 text-zinc-400 pointer-events-none"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="search" wire:model.live.debounce.300ms="{{ $searchModel }}"
                   placeholder="{{ $searchPlaceholder ?? __('home.ct_rep_search_in_list') }}"
                   class="{{ $inp }} pr-8 py-1.5 text-xs">
        </div>

        <select wire:model="{{ $valueModel }}" class="{{ $inp }}" size="1">
            <option value="">{{ $placeholder }}</option>
            @foreach($options as $option)
                <option value="{{ $option['id'] }}" title="{{ $option['title'] ?? $option['label'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>

        @if($searchModel && $this->{$searchModel} !== '')
            <p class="text-xs {{ count($options) ? 'text-zinc-400 dark:text-zinc-500' : 'text-amber-600 dark:text-amber-400' }}">
                {{ count($options) ? __('home.ct_rep_matches', ['count' => count($options)]) : __('home.ct_rep_no_matches') }}
            </p>
        @endif
    </div>
</div>
