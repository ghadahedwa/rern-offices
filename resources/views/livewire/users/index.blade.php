<div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ __('home.users') }}</h1>
            <a href="{{ route('users.create') }}" wire:navigate
               class="inline-flex items-center gap-2 bg-[#c9a847] hover:bg-[#b8962e] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('home.add_user') }}
            </a>
        </div>

        {{-- Search & Filters --}}
        <div class="flex flex-wrap gap-3">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="{{ __('home.search') }}"
                class="w-64 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]"
            />
            <select
                wire:model.live="roleFilter"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]"
            >
                <option value="">{{ __('home.all_roles') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <select
                wire:model.live="entityFilter"
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-[#c9a847]"
            >
                <option value="">{{ __('home.all_entities') }}</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm">
            <table class="w-full table-fixed text-[13px] text-right">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 text-[11px] uppercase">
                    <tr>
                        {{-- مجموع النِسَب ١٠٠٪ بالضبط — الناقص يوزّعه المتصفح كما يشاء --}}
                        <th class="px-2 py-2.5 font-medium w-[4%]">#</th>
                        <th class="px-2 py-2.5 font-medium w-[18%]">{{ __('home.name') }}</th>
                        <th class="px-2 py-2.5 font-medium w-[12%]">{{ __('home.username') }}</th>
                        <th class="px-2 py-2.5 font-medium w-[16%]">{{ __('home.role') }}</th>
                        {{-- الطرف والمسمّى في عمود واحد: بيانا نطاق واحد (المراسلات)، ودمجهما يفرّج للدور --}}
                        <th class="px-2 py-2.5 font-medium w-[20%]">{{ __('home.user_entity_and_title') }}</th>
                        <th class="px-2 py-2.5 font-medium w-[14%]">{{ __('home.governorates') }}</th>
                        <th class="px-2 py-2.5 font-medium w-[16%]">{{ __('home.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($users as $user)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-2 py-2.5 text-zinc-500">{{ $users->firstItem() + $loop->index }}</td>
                            <td class="px-2 py-2.5 font-medium text-zinc-800 dark:text-zinc-100 truncate" title="{{ $user->name }}">
                                {{ $user->name }}
                            </td>
                            <td class="px-2 py-2.5 text-zinc-600 dark:text-zinc-300 truncate" title="{{ $user->username }}">
                                {{ $user->username }}
                            </td>
                            <td class="px-2 py-2.5">
                                @if($user->roles->first())
                                    <span class="inline-block max-w-full truncate px-2 py-0.5 rounded-full text-[11px] font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]"
                                          title="{{ $user->roles->first()->name }}">
                                        {{ $user->roles->first()->name }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2.5">
                                @if($user->correspondenceEntity || $user->job_title)
                                    @if($user->correspondenceEntity)
                                        <span class="inline-block max-w-full truncate px-2 py-0.5 rounded-full text-[11px] font-medium bg-[#c9a847]/15 text-[#b8962e] dark:text-[#c9a847]"
                                              title="{{ $user->correspondenceEntity->name }}">
                                            {{ $user->correspondenceEntity->name }}
                                        </span>
                                    @endif
                                    @if($user->job_title)
                                        <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5" title="{{ $user->job_title }}">
                                            {{ $user->job_title }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2.5">
                                @if($user->governorates->isNotEmpty())
                                    {{-- شارتان فقط ثم «+N» — المستخدم قد يُربط بـ٢٧ محافظة، فسردها كلها يمدّ الصف --}}
                                    <div class="flex flex-wrap gap-1" title="{{ $user->governorates->pluck('name')->implode(' · ') }}">
                                        {{-- الأزرق نفسه المعتمَد في الموك-أب: #e8effb / #2c5aa8 --}}
                                        @foreach($user->governorates->take(2) as $gov)
                                            <span class="inline-block max-w-full truncate px-2 py-0.5 rounded-full text-[11px] font-medium bg-[#e8effb] text-[#2c5aa8] dark:bg-[#2c5aa8]/25 dark:text-[#a9c4ee]">
                                                {{ $gov->name }}
                                            </span>
                                        @endforeach
                                        @if($user->governorates->count() > 2)
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-medium bg-[#e8effb] text-[#2c5aa8] dark:bg-[#2c5aa8]/25 dark:text-[#a9c4ee]">
                                                +{{ $user->governorates->count() - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2.5">
                                @if($user->id !== 1)
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('users.edit', $user) }}" wire:navigate
                                       class="inline-flex items-center text-[11px] px-2.5 py-1 rounded-md border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                                        {{ __('home.edit') }}
                                    </a>
                                    <button
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="{{ __('home.confirm_delete') }}"
                                        class="inline-flex items-center text-[11px] px-2.5 py-1 rounded-md border border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        {{ __('home.delete') }}
                                    </button>
                                </div>
                                @else
                                <span class="text-[11px] text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-10 text-center text-zinc-400">
                                {{ __('home.no_users') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $users->links() }}
        </div>

</div>
