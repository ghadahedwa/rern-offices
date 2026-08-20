<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="dark">
    <head>
        @include('partials.head')
        <style>
            html { font-size: 17px; }
            * { font-size: inherit; }
            [data-flux-sidebar-item],
            [data-flux-sidebar-item] span,
            [data-flux-sidebar-item] a,
            [data-flux-sidebar] .nested-menu-btn,
            [data-flux-sidebar] .nested-menu-btn span { font-size: 0.85rem !important; }
            [data-flux-sidebar]:not([data-flux-sidebar-collapsed-desktop]) { width: 15rem; }
            [data-flux-sidebar] { z-index: 30 !important; }
            /* اللوجو (الهيدر) يفضل ثابت فوق، والقائمة تتوسّع لتحت وتعمل scroll في منطقتها لوحدها
               (نمط Slack/Notion) — بدل ما القائمة كلها تتحرّك وتعدّي فوق اللوجو. */
            [data-flux-sidebar] { overflow: hidden !important; }
            [data-flux-sidebar] [data-flux-sidebar-header] { flex-shrink: 0; }
            [data-flux-sidebar] [data-flux-sidebar-nav] {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                overflow-x: hidden;
            }
            [data-flux-sidebar-item],
            [data-flux-sidebar-item] span,
            [data-flux-sidebar] .nested-menu-btn,
            [data-flux-sidebar] .nested-menu-btn span { white-space: normal !important; word-break: break-word; }
        </style>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 text-base">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header class="flex flex-col items-center gap-1 pb-0">
                <div class="w-full flex justify-center">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>
                <div class="w-full flex justify-end -mt-3">
                    <flux:sidebar.collapse class="text-[#c9a847]" />
                </div>
            </flux:sidebar.header>
            <flux:sidebar.nav class="overflow-y-auto">
                @php
                    $currentBranch      = \App\Support\Branch::current();
                    $accessibleBranches = \App\Support\Branch::accessibleFor();
                    $currentBranchConf  = \App\Support\Branch::config($currentBranch);
                @endphp

                {{-- مبدّل الفرع — يظهر فقط لو المستخدم عنده أكتر من فرع --}}
                @if(count($accessibleBranches) > 1)
                    <flux:dropdown position="bottom" align="start" class="w-full mb-2">
                        <button type="button"
                            class="flex items-center gap-2 w-full px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-[#c9a847] transition">
                            <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex-1 text-start">
                                {{ $currentBranchConf ? __($currentBranchConf['label']) : '' }}
                            </span>
                            <svg class="w-4 h-4 text-[#c9a847] shrink-0" viewBox="0 0 20 20" fill="none">
                                <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <flux:menu>
                            @foreach($accessibleBranches as $bKey)
                                @php $b = \App\Support\Branch::config($bKey); @endphp
                                <flux:menu.item :href="\App\Support\Branch::entryUrlFor($bKey)" icon="{{ $b['icon'] }}"
                                    class="{{ $bKey === $currentBranch ? 'text-[#c9a847]!' : '' }}" wire:navigate>
                                    {{ __($b['label']) }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                @endif

                <flux:sidebar.group class="grid">
                    @if($currentBranch === 'offices')
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('home.dashboard') }}
                    </flux:sidebar.item>
                    @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('governorates.index'))
                    <flux:sidebar.item icon="map-pin" :href="route('governorates.index')" :current="request()->routeIs('governorates.*')" wire:navigate>
                        {{ __('home.governorates') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('claims.index'))
                    <flux:sidebar.item icon="banknotes" :href="route('claims.index')" :current="request()->routeIs('claims.*')" wire:navigate>
                        {{ __('home.claims_title') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.index'))
                    <flux:sidebar.item icon="building-office-2" :href="route('offices.index')" :current="request()->routeIs('offices.index') || request()->routeIs('offices.create') || request()->routeIs('offices.show') || request()->routeIs('offices.edit') || request()->routeIs('offices.statistics')" wire:navigate>
                        {{ __('home.offices') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('vehicles.index'))
                    <flux:sidebar.item icon="truck" :href="route('vehicles.index')" :current="request()->routeIs('vehicles.*')" wire:navigate>
                        {{ __('home.vehicles_title') }}
                    </flux:sidebar.item>
                    @endif
                    @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.phone-directory'))
                    <flux:sidebar.item icon="phone" :href="route('offices.phone-directory')" :current="request()->routeIs('offices.phone-directory')" wire:navigate>
                        {{ __('home.phone_directory_title') }}
                    </flux:sidebar.item>
                    @endif
                    @php
                        $canOfficeReports  = auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.export');
                        $canVehicleReports = auth()->user()?->hasRole('super-admin') || auth()->user()?->can('vehicles.export');
                        $canClaimsReports  = auth()->user()?->hasRole('super-admin') || auth()->user()?->can('claims.export');
                    @endphp
                    {{-- ثلاث قوائم تقارير مستقلة (لا قائمة واحدة بـ١٢ بنداً مسطّحاً).
                         كل قائمة تُفتح وحدها حين تكون الصفحة الحالية من راوتاتها،
                         فلا يبحث المستخدم في تقارير نوع آخر عن التقرير الذي هو فيه.
                         ⚠️ راوت تقرير جديد يُضاف لقائمة `$routes` الخاصة بمجموعته
                            وإلا فُتحت المجموعة الخاطئة (أو لم تُفتح واحدة). --}}
                    @php
                        $reportGroups = [
                            [
                                'can'    => $canOfficeReports,
                                'label'  => 'home.reports_offices',
                                'routes' => ['reports.office-pdf', 'reports.multi-office', 'reports.offices-by-type', 'reports.device-count', 'reports.stats-comparison'],
                                'items'  => [
                                    ['reports.office-pdf',      'document-text',     'home.report_office_pdf_title'],
                                    ['reports.multi-office',    'document-duplicate', 'home.report_multi_title'],
                                    ['reports.offices-by-type', 'table-cells',       'home.report_by_type_menu'],
                                    ['reports.device-count',    'computer-desktop',  'home.report_devices_title'],
                                    ['reports.stats-comparison', 'chart-bar',        'home.report_stats_menu'],
                                ],
                            ],
                            [
                                'can'    => $canVehicleReports,
                                'label'  => 'home.reports_vehicles',
                                'routes' => ['reports.multi-vehicle', 'reports.vehicle-device-count', 'reports.vehicle-status', 'reports.vehicle-coverage', 'reports.vehicle-licenses'],
                                'items'  => [
                                    ['reports.multi-vehicle',        'truck',               'home.report_vehicle_multi_title'],
                                    ['reports.vehicle-device-count', 'wrench-screwdriver',  'home.report_vehicle_devices_title'],
                                    ['reports.vehicle-status',       'chart-pie',           'home.report_vehicle_status_title'],
                                    ['reports.vehicle-coverage',     'calendar-days',       'home.report_vehicle_coverage_title'],
                                    ['reports.vehicle-licenses',     'identification',      'home.report_vehicle_licenses_title'],
                                ],
                            ],
                            [
                                'can'    => $canClaimsReports,
                                'label'  => 'home.reports_claims',
                                'routes' => ['reports.claims-statement', 'reports.claims-summary'],
                                'items'  => [
                                    ['reports.claims-statement', 'banknotes', 'home.claims_statement_title'],
                                    ['reports.claims-summary',   'scale',     'home.claims_summary_title'],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach($reportGroups as $group)
                        @if($group['can'])
                        <div x-data="{ open: {{ request()->routeIs(...$group['routes']) ? 'true' : 'false' }} }">
                            <button @click="open = !open"
                                class="nested-menu-btn flex items-center w-full px-3 py-2 font-medium rounded text-zinc-600 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                                <span class="ml-2">{{ __($group['label']) }}</span>
                                <svg class="ml-auto w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20">
                                    <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="ml-6 mt-1 space-y-1">
                                @foreach($group['items'] as [$routeName, $icon, $labelKey])
                                <flux:sidebar.item :icon="$icon" :href="route($routeName)" :current="request()->routeIs($routeName)" wire:navigate>
                                    {{ __($labelKey) }}
                                </flux:sidebar.item>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                    @endif {{-- /branch: offices --}}

                    @if($currentBranch === 'meetings')
                    <flux:sidebar.item icon="calendar-days" :href="route('meetings.index')" :current="request()->routeIs('meetings.*')" wire:navigate>
                        {{ __('home.meetings_title') }}
                    </flux:sidebar.item>
                    @endif {{-- /branch: meetings --}}

                    @if($currentBranch === 'warehouses')
                    <flux:sidebar.item icon="squares-2x2" :href="route('warehouses.dashboard')" :current="request()->routeIs('warehouses.dashboard')" wire:navigate>
                        {{ __('home.warehouses_dashboard') }}
                    </flux:sidebar.item>

                    @if(auth()->user()?->can('warehouses.settings'))
                    <flux:sidebar.item icon="building-office-2" :href="route('warehouse-manage.index')" :current="request()->routeIs('warehouse-manage.*')" wire:navigate>
                        {{ __('home.warehouses_manage_title') }}
                    </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item icon="scale" :href="route('warehouses.stock')" :current="request()->routeIs('warehouses.stock')" wire:navigate>
                        {{ __('home.wh_stock') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="inbox-arrow-down" :href="route('warehouses.incoming.index')" :current="request()->routeIs('warehouses.incoming.*')" wire:navigate>
                        {{ __('home.wh_incoming') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="arrows-right-left" :href="route('warehouses.transfers.index')" :current="request()->routeIs('warehouses.transfers.*')" wire:navigate>
                        {{ __('home.wh_transfers') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="queue-list" :href="route('warehouses.movements')" :current="request()->routeIs('warehouses.movements')" wire:navigate>
                        {{ __('home.wh_movements') }}
                    </flux:sidebar.item>

                    @if(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('warehouses.create'))
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('warehouses.opening-balances')" :current="request()->routeIs('warehouses.opening-balances')" wire:navigate>
                        {{ __('home.wh_opening_balances') }}
                    </flux:sidebar.item>
                    @endif

                    @endif {{-- /branch: warehouses --}}

                    @if($currentBranch === 'correspondence')
                    {{-- زر «مكاتبة جديدة»: الإنشاء فعل لا مكان، فزرّ لا بند —
                         وهو نمط المشروع (زرّ «إضافة» في رأس كل قائمة). سقالة حتى تُبنى الشاشة. --}}
                    @if(auth()->user()?->can('correspondence.create'))
                        <div class="px-1 pb-2">
                            <span class="flex items-center justify-center gap-2 w-full bg-[#c9a847]/15 text-[#b8962e] dark:text-[#d8b856] border border-[#c9a847]/40 text-xs font-semibold px-3 py-2 rounded-lg cursor-not-allowed"
                                  title="{{ __('home.corr_placeholder_note') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('home.corr_new') }}
                            </span>
                        </div>
                    @endif

                    {{-- العدّادات مكوّن Livewire بـpoll — داخل الفرع وحده --}}
                    @livewire('correspondence.menu-counters')
                    @endif {{-- /branch: correspondence --}}

                    @if($currentBranch === 'feedback')
                    {{-- الشاشات الثلاث بـfeedback.view، والمرفوضات بصلاحيتها المستقلة —
                         رابط يؤدي إلى ٤٠٣ أسوأ من غيابه --}}
                    @can('feedback.view')
                    <flux:sidebar.item icon="squares-2x2" :href="route('feedback-results.dashboard')" :current="request()->routeIs('feedback-results.dashboard')" wire:navigate>
                        {{ __('home.fr_dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="star" :href="route('feedback-results.ratings')" :current="request()->routeIs('feedback-results.ratings')" wire:navigate>
                        {{ __('home.fr_ratings') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="light-bulb" :href="route('feedback-results.suggestions')" :current="request()->routeIs('feedback-results.suggestions')" wire:navigate>
                        {{ __('home.fr_suggestions') }}
                    </flux:sidebar.item>
                    @endcan

                    @can('feedback.rejected')
                    <flux:sidebar.item icon="shield-exclamation" :href="route('feedback-results.rejected')" :current="request()->routeIs('feedback-results.rejected')" wire:navigate>
                        {{ __('home.fr_rejected') }}
                    </flux:sidebar.item>
                    @endcan
                    @endif {{-- /branch: feedback --}}

                    @if($currentBranch === 'system')

                    <flux:sidebar.item icon="squares-2x2" :href="route('system-dashboard')" :current="request()->routeIs('system-dashboard')" wire:navigate>
                        {{ __('home.system_dashboard') }}
                    </flux:sidebar.item>

                    {{-- المستخدمون والأدوار — super-admin فقط حالياً --}}
                    @if(auth()->user()?->hasRole('super-admin'))
                    <div x-data="{ open: {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="nested-menu-btn flex items-center w-full px-3 py-2 font-medium rounded text-zinc-600 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            <span>⚙️</span>
                            <span class="ml-2">{{ __('home.users_settings') }}</span>
                            <svg class="ml-auto w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20">
                                <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="ml-6 mt-1 space-y-1 overflow-hidden [&_[data-content]]:text-xs! [&_[data-content]]:min-w-0">
                            <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                                {{ __('home.users') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                                {{ __('home.roles') }}
                            </flux:sidebar.item>
                        </div>
                    </div>
                    @endif

                    {{-- إعدادات المخازن (الأصناف + الأنواع + الوحدات — إدارة المخازن نفسها انتقلت لفرع المخازن) — صلاحية warehouses.settings --}}
                    @if(auth()->user()?->can('warehouses.settings'))
                    <div x-data="{ open: {{ request()->routeIs('items.*') || request()->routeIs('warehouse-types.*') || request()->routeIs('item-units.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="nested-menu-btn flex items-center w-full px-3 py-2 font-medium rounded text-zinc-600 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span class="ml-2">{{ __('home.warehouse_settings') }}</span>
                            <svg class="ml-auto w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20">
                                <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="ml-6 mt-1 space-y-1 overflow-hidden [&_[data-content]]:text-xs! [&_[data-content]]:min-w-0">
                            <flux:sidebar.item icon="cube" :href="route('items.index')" :current="request()->routeIs('items.*')" wire:navigate>
                                {{ __('home.items_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="tag" :href="route('warehouse-types.index')" :current="request()->routeIs('warehouse-types.*')" wire:navigate>
                                {{ __('home.warehouse_types_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="scale" :href="route('item-units.index')" :current="request()->routeIs('item-units.*')" wire:navigate>
                                {{ __('home.item_units_title') }}
                            </flux:sidebar.item>
                        </div>
                    </div>
                    @endif

                    {{-- إعدادات المراسلات (أطراف المراسلات) — صلاحية correspondence.settings --}}
                    @if(auth()->user()?->can('correspondence.settings'))
                    <div x-data="{ open: {{ request()->routeIs('correspondence-entities.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="nested-menu-btn flex items-center w-full px-3 py-2 font-medium rounded text-zinc-600 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            <span class="ml-2">{{ __('home.corr_settings') }}</span>
                            <svg class="ml-auto w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 20 20">
                                <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="ml-6 mt-1 space-y-1 overflow-hidden [&_[data-content]]:text-xs! [&_[data-content]]:min-w-0">
                            <flux:sidebar.item icon="building-library" :href="route('correspondence-entities.index')" :current="request()->routeIs('correspondence-entities.*')" wire:navigate>
                                {{ __('home.corr_entities_title') }}
                            </flux:sidebar.item>
                        </div>
                    </div>
                    @endif

                    {{-- إعدادات المقرات (القوائم المرجعية للمقرات والسيارات) --}}
                    @if(auth()->user()?->can('offices.settings'))
                    <div x-data="{ open: {{ request()->routeIs('office-types.*') || request()->routeIs('location-descriptions.*') || request()->routeIs('work-systems.*') || request()->routeIs('working-hours.*') || request()->routeIs('connection-types.*') || request()->routeIs('device-types.*') || request()->routeIs('contractual-statuses.*') || request()->routeIs('structural-conditions.*') || request()->routeIs('disabilities-access.*') || request()->routeIs('fire-safety.*') || request()->routeIs('document-photocopying-services.*') || request()->routeIs('buffet-services.*') || request()->routeIs('cleanliness-contracts.*') || request()->routeIs('microfilm-options.*') || request()->routeIs('vehicle-types.*') || request()->routeIs('vehicle-brands.*') || request()->routeIs('vehicle-work-systems.*') || request()->routeIs('vehicle-working-hours.*') || request()->routeIs('vehicle-device-types.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="nested-menu-btn flex items-center w-full px-3 py-2 font-medium rounded text-zinc-600 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <span class="ml-2">{{ __('home.program_settings') }}</span>
                            <svg class="ml-auto w-4 h-4 transition-transform"
                                :class="{ 'rotate-180': open }"
                                viewBox="0 0 20 20">
                                <path d="M5 8l5 5 5-5" fill="none" stroke="currentColor"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="ml-6 mt-1 space-y-1 overflow-hidden [&_[data-content]]:text-xs! [&_[data-content]]:min-w-0">
                            <flux:sidebar.item icon="tag" :href="route('office-types.index')" :current="request()->routeIs('office-types.*')" wire:navigate>
                                {{ __('home.offices_type') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="map-pin" :href="route('location-descriptions.index')" :current="request()->routeIs('location-descriptions.*')" wire:navigate>
                                {{ __('home.location_description') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clock" :href="route('work-systems.index')" :current="request()->routeIs('work-systems.*')" wire:navigate>
                                {{ __('home.work_systems') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calendar-days" :href="route('working-hours.index')" :current="request()->routeIs('working-hours.*')" wire:navigate>
                                {{ __('home.working_hours_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="signal" :href="route('connection-types.index')" :current="request()->routeIs('connection-types.*')" wire:navigate>
                                {{ __('home.connection_types') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="computer-desktop" :href="route('device-types.index')" :current="request()->routeIs('device-types.*')" wire:navigate>
                                {{ __('home.device_types') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="document-text" :href="route('contractual-statuses.index')" :current="request()->routeIs('contractual-statuses.*')" wire:navigate>
                                {{ __('home.contractual_statuses') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="building-office-2" :href="route('structural-conditions.index')" :current="request()->routeIs('structural-conditions.*')" wire:navigate>
                                {{ __('home.structural_conditions') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="user-group" :href="route('disabilities-access.index')" :current="request()->routeIs('disabilities-access.*')" wire:navigate>
                                {{ __('home.disabilities_access_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="fire" :href="route('fire-safety.index')" :current="request()->routeIs('fire-safety.*')" wire:navigate>
                                {{ __('home.fire_safety_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="document-duplicate" :href="route('document-photocopying-services.index')" :current="request()->routeIs('document-photocopying-services.*')" wire:navigate>
                                {{ __('home.document_photocopying_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="cake" :href="route('buffet-services.index')" :current="request()->routeIs('buffet-services.*')" wire:navigate>
                                {{ __('home.buffet_service_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="sparkles" :href="route('cleanliness-contracts.index')" :current="request()->routeIs('cleanliness-contracts.*')" wire:navigate>
                                {{ __('home.cleanliness_contract_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="film" :href="route('microfilm-options.index')" :current="request()->routeIs('microfilm-options.*')" wire:navigate>
                                {{ __('home.microfilm_option_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="truck" :href="route('vehicle-types.index')" :current="request()->routeIs('vehicle-types.*')" wire:navigate>
                                {{ __('home.vehicle_types_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="tag" :href="route('vehicle-brands.index')" :current="request()->routeIs('vehicle-brands.*')" wire:navigate>
                                {{ __('home.vehicle_brands_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clock" :href="route('vehicle-work-systems.index')" :current="request()->routeIs('vehicle-work-systems.*')" wire:navigate>
                                {{ __('home.vehicle_work_systems_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calendar-days" :href="route('vehicle-working-hours.index')" :current="request()->routeIs('vehicle-working-hours.*')" wire:navigate>
                                {{ __('home.vehicle_working_hours_title') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="computer-desktop" :href="route('vehicle-device-types.index')" :current="request()->routeIs('vehicle-device-types.*')" wire:navigate>
                                {{ __('home.vehicle_device_types_title') }}
                            </flux:sidebar.item>
                        </div>
                    </div>
                    @endif
                    @endif {{-- /branch: system --}}
                    
                </flux:sidebar.group>
                
            </flux:sidebar.nav>
        </flux:sidebar>

        {{-- Navbar: visible on all screen sizes --}}
        <flux:header class="border-b border-[#b8962e]" style="background-color: #c9a847;">
            {{-- Mobile sidebar toggle --}}
            <flux:sidebar.toggle class="lg:hidden text-[#c9a847]" icon="bars-2" inset="left" />

            <flux:spacer />

            {{-- ✉️ الظرف — المؤشّر الوحيد الظاهر لمن يعمل في فرع آخر.
                 قائمة الفروع dropdown مقفول، فعدّادٌ عليها يكون مدفوناً.
                 blade لا Livewire: يتحدّث مع كل تنقّل (الشريط ليس داخل @persist)،
                 والـpoll محصور في عدّادات منيو الفرع. ولا قائمة منسدلة — تلك شغل الجرس. --}}
            @php $counters = app(\App\Support\CorrespondenceCounters::class); @endphp
            @if($counters->envelopeVisible())
                @php $inboxCount = $counters->inbox(); @endphp
                <a href="{{ route('correspondence.inbox') }}" wire:navigate
                   class="relative flex items-center justify-center w-9 h-9 rounded-lg hover:bg-black/10 transition"
                   title="{{ $inboxCount > 0 ? __('home.corr_envelope_title', ['count' => $inboxCount]) : __('home.corr_envelope_empty') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    @if($inboxCount > 0)
                        <span class="absolute -top-0.5 -end-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold grid place-items-center">
                            {{ $inboxCount > 99 ? '99+' : $inboxCount }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- User dropdown with gold styling --}}
            <flux:dropdown position="bottom" align="end">
                <button type="button"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-black/10 transition">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-[#c9a847] text-sm font-bold"
                         style="background-color: rgba(255,255,255,0.25);">
                        {{ auth()->user()->initials() }}
                    </div>
                    <span class="hidden sm:block text-sm font-semibold text-white">
                        {{ auth()->user()->name }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <flux:menu>
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                        />
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts

        {{-- حفظ موضع الـ scroll عند النقر على pagination --}}
        <script>
            document.addEventListener('livewire:initialized', function () {
                var savedScroll = null;

                document.addEventListener('click', function (e) {
                    if (e.target.closest('[wire\\:click*="Page"]') || e.target.closest('[wire\\:click*="page"]')) {
                        savedScroll = window.scrollY;
                    }
                });

                Livewire.hook('commit', function (ref) {
                    var succeed = ref.succeed;
                    if (savedScroll !== null) {
                        var scroll = savedScroll;
                        savedScroll = null;
                        succeed(function () {
                            requestAnimationFrame(function () {
                                window.scrollTo({ top: scroll, behavior: 'instant' });
                            });
                        });
                    }
                });
            });
        </script>

        {{-- معالجة "This page has expired" (419): حفظ البيانات وإعادة التحميل بصمت ثم استرجاعها --}}
        <script>
            (function () {
                const fieldSelector = '[wire\\:model], [wire\\:model\\.live], [wire\\:model\\.blur], [wire\\:model\\.lazy], [wire\\:model\\.defer]';

                const backupKey = () => 'form_backup:' + window.location.pathname + window.location.search;

                const modelName = (el) =>
                    el.getAttribute('wire:model')
                    || el.getAttribute('wire:model.live')
                    || el.getAttribute('wire:model.blur')
                    || el.getAttribute('wire:model.lazy')
                    || el.getAttribute('wire:model.defer');

                function collectFields() {
                    const data = {};
                    document.querySelectorAll(fieldSelector).forEach((el) => {
                        const name = modelName(el);
                        if (!name || el.type === 'file') return;
                        if (el.type === 'checkbox') {
                            data[name] = el.checked;
                        } else if (el.type === 'radio') {
                            if (el.checked) data[name] = el.value;
                        } else {
                            data[name] = el.value;
                        }
                    });
                    return data;
                }

                function restoreFields(data) {
                    Object.entries(data).forEach(([name, value]) => {
                        const esc = name.replace(/(["\\])/g, '\\$1');
                        const els = document.querySelectorAll(
                            '[wire\\:model="' + esc + '"],[wire\\:model\\.live="' + esc + '"],[wire\\:model\\.blur="' + esc + '"],[wire\\:model\\.lazy="' + esc + '"],[wire\\:model\\.defer="' + esc + '"]'
                        );
                        els.forEach((el) => {
                            if (el.type === 'file') return;
                            if (el.type === 'checkbox') {
                                el.checked = !!value;
                            } else if (el.type === 'radio') {
                                el.checked = (el.value == value);
                            } else {
                                el.value = value;
                            }
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    });
                }

                // الاسترجاع — يُستدعى بعد ما Livewire يجهّز الحقول فعلاً
                function tryRestore() {
                    if (! document.querySelector('[data-form-recovery]')) return;
                    const saved = sessionStorage.getItem(backupKey());
                    if (! saved) return;
                    try { restoreFields(JSON.parse(saved)); } catch (e) {}
                    sessionStorage.removeItem(backupKey());
                }

                // تسجيل اعتراض الـ 419 (Livewire 4): حفظ البيانات ثم إعادة التحميل
                document.addEventListener('livewire:init', () => {
                    Livewire.interceptRequest(({ onError }) => {
                        onError(({ response, preventDefault }) => {
                            if (! response || response.status !== 419) return;
                            preventDefault(); // يمنع رسالة "This page has expired" الإنجليزية
                            if (document.querySelector('[data-form-recovery]')) {
                                try {
                                    sessionStorage.setItem(backupKey(), JSON.stringify(collectFields()));
                                } catch (e) {}
                            }
                            window.location.reload();
                        });
                    });
                });

                // الاسترجاع يشتغل بعد تجهيز Livewire الكامل (وكذلك بعد تنقّل SPA)
                document.addEventListener('livewire:initialized', tryRestore);
                document.addEventListener('livewire:navigated', tryRestore);
            })();
        </script>
    </body>
</html>
