{{-- Welcome Banner --}}
<div class="rounded-xl border border-[#c9a847]/30 bg-transparent p-6 flex items-center justify-between">
    <div>
        <p class="text-zinc-400 dark:text-zinc-500 text-sm mb-1">{{ __('home.welcome_back') }}</p>
        <h2 class="text-2xl font-bold text-[#b8962e] dark:text-[#c9a847]">{{ $user->name }}</h2>
        <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-1">{{ __('home.welcome_subtitle') }}</p>
    </div>
    <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-full border-2 border-[#c9a847]/40 bg-[#c9a847]/15 dark:bg-[#c9a847]/20">
        <flux:icon.building-office-2 variant="outline" class="w-8 h-8 text-[#b8962e] dark:text-[#c9a847]" />
    </div>
</div>
