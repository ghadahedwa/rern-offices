{{-- منسدلة فلتر: تسمية فوق العنصر، وخياراتها في الـslot. --}}
@props(['label' => null, 'wrapper' => ''])

<div class="{{ $wrapper }}">
    @if($label)
        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ $label }}</label>
    @endif
    <select {{ $attributes->merge(['class' => 'w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]']) }}>
        {{ $slot }}
    </select>
</div>
