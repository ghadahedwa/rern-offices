{{-- بنود منيو المراسلات — ‎wire:poll.300s‎ يوقف تلقائياً للتاب في الخلفية --}}
<div wire:poll.300s class="grid gap-1">

    @php
        // شارة العدّاد: تظهر عند وجود رقم فقط — بند بصفر أحمر يفقد معناه
        $badge = 'ms-auto min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold grid place-items-center shrink-0';
    @endphp

    <flux:sidebar.item icon="inbox-arrow-down" :href="route('correspondence.inbox')"
                       :current="request()->routeIs('correspondence.inbox')" wire:navigate>
        <span class="flex items-center w-full">
            {{ __('home.corr_inbox') }}
            @if($inboxCount > 0)<span class="{{ $badge }}">{{ $inboxCount > 99 ? '99+' : $inboxCount }}</span>@endif
        </span>
    </flux:sidebar.item>

    <flux:sidebar.item icon="paper-airplane" :href="route('correspondence.outbox')"
                       :current="request()->routeIs('correspondence.outbox')" wire:navigate>
        {{ __('home.corr_outbox') }}
    </flux:sidebar.item>

    @if(auth()->user()?->can('correspondence.create') || auth()->user()?->can('correspondence.approve'))
        <flux:sidebar.item icon="pencil-square" :href="route('correspondence.drafts')"
                           :current="request()->routeIs('correspondence.drafts')" wire:navigate>
            <span class="flex items-center w-full">
                {{ __('home.corr_drafts') }}
                @if($draftsCount > 0)<span class="{{ $badge }}">{{ $draftsCount > 99 ? '99+' : $draftsCount }}</span>@endif
            </span>
        </flux:sidebar.item>
    @endif

    <flux:sidebar.item icon="clipboard-document-list" :href="route('correspondence.assignments')"
                       :current="request()->routeIs('correspondence.assignments')" wire:navigate>
        <span class="flex items-center w-full">
            {{ __('home.corr_assignments') }}
            @if($assignmentsCount > 0)<span class="{{ $badge }}">{{ $assignmentsCount > 99 ? '99+' : $assignmentsCount }}</span>@endif
        </span>
    </flux:sidebar.item>

    {{-- التفويض لرئيس الجهة وحده — بلا عدّاد: إعداد لا مهام --}}
    @if(auth()->user()?->can('correspondence.delegate'))
        <flux:sidebar.item icon="user-group" :href="route('correspondence.delegations')"
                           :current="request()->routeIs('correspondence.delegations')" wire:navigate>
            {{ __('home.corr_delegations') }}
        </flux:sidebar.item>
    @endif

</div>
