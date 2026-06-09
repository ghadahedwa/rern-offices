{{-- Online Users — صف أفقي (super-admin/مشرف: الفريق، مفتش: نفسه) --}}
<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm p-4">
    <div class="flex items-center gap-3 mb-3">
        <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
            {{ __('home.online_users_title') }}
        </h3>
        <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ $onlineUsers->count() }}
        </span>
    </div>

    @if($onlineUsers->isEmpty())
        <p class="text-sm text-zinc-400">{{ __('home.online_users_empty') }}</p>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach($onlineUsers as $u)
            <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <div class="w-6 h-6 rounded-full bg-[#c9a847]/15 flex items-center justify-center text-xs font-bold text-[#b8962e]">
                    {{ mb_substr($u->name, 0, 1) }}
                </div>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $u->name }}</span>
                <span class="text-xs text-zinc-400">{{ $u->ip_address }}</span>
            </div>
            @endforeach
        </div>
    @endif
</div>
