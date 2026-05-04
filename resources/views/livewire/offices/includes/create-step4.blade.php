
            {{-- ── Section: الوسائط ── --}}
            <div class="mb-1"
                 x-data="{
                     lightbox: false,
                     lightboxSrc: '',
                     videoModal: false,
                     videoSrc: '',
                     openPhoto(src) { this.lightboxSrc = src; this.lightbox = true; },
                     openVideo(src) { this.videoSrc = src; this.videoModal = true; },
                     closeAll() {
                         this.lightbox = false;
                         this.videoModal = false;
                         this.$nextTick(() => { this.lightboxSrc = ''; this.videoSrc = ''; });
                     }
                 }">

                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.step_4_label') }}</h3>
                </div>

                @php
                    $fileInput           = 'w-full text-sm text-zinc-500 file:ml-0 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-[#c9a847]/10 file:text-[#b8962e] hover:file:bg-[#c9a847]/20 cursor-pointer';
                    $clearBtn            = 'text-xs text-red-500 hover:text-red-700 transition font-medium shrink-0';
                    $existingPhotosCount = ($existingMedia['photo'] ?? collect())->count();
                    $remaining           = max(0, 5 - $existingPhotosCount);
                @endphp

                <div class="space-y-6">
<flux:modal.trigger name="edit-profile">
    <flux:button>Add Photo</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" class="md:w-96">
    <flux:input type="file" wire:model="photo" label="Logo" />
</flux:modal>
                    {{-- ── صور المقر ── --}}
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">صور المقر</label>
                            <span class="text-xs text-zinc-400">{{ $existingPhotosCount }} / 5</span>
                        </div>

                        {{-- صور موجودة --}}
                        @if($existingPhotosCount > 0)
                            <div class="flex flex-nowrap gap-2 overflow-x-auto pb-2 mb-4">
                                @foreach($existingMedia['photo'] as $photo)
                                    <div class="shrink-0 flex flex-col items-center gap-1">
                                        <img src="{{ Storage::url($photo->path) }}"
                                             @click="openPhoto('{{ Storage::url($photo->path) }}')"
                                             class="w-16 h-16 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:opacity-80 transition" />
                                        <button type="button"
                                                wire:click="deleteMedia({{ $photo->id }})"
                                                wire:confirm="حذف هذه الصورة؟"
                                                class="text-xs text-red-500 hover:text-red-700 transition">حذف</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- رفع صور جديدة --}}
                        @if($remaining > 0)
                            <div class="flex items-center gap-3">
                                <input type="file" wire:model="newPhotos" multiple accept="image/*" class="{{ $fileInput }} flex-1" />
                                @if($newPhotos)
                                    <button type="button" wire:click="clearPhotos" class="{{ $clearBtn }}">مسح</button>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-400 mt-1">الحد الأقصى {{ $remaining }} صورة متبقية — 5 ميجا لكل صورة</p>
                            @error('newPhotos')   <p class="{{ $err }}">{{ $message }}</p> @enderror
                            @error('newPhotos.*') <p class="{{ $err }}">{{ $message }}</p> @enderror

                            {{-- معاينة الصور الجديدة --}}
                            @if($newPhotos)
                                <div class="flex flex-nowrap gap-2 overflow-x-auto mt-3 pb-1">
                                    @foreach($newPhotos as $photo)
                                        <div class="shrink-0 flex flex-col items-center gap-1">
                                            <img src="{{ $photo->temporaryUrl() }}"
                                                 @click="openPhoto('{{ $photo->temporaryUrl() }}')"
                                                 class="w-16 h-16 object-cover rounded-lg border border-[#c9a847]/50 cursor-pointer hover:opacity-80 transition" />
                                            <span class="text-xs text-zinc-400 truncate w-16 text-center">{{ $photo->getClientOriginalName() }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="text-xs text-amber-600">تم الوصول للحد الأقصى (5 صور)</p>
                        @endif
                    </div>

                    {{-- ── فيديو المقر ── --}}
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3 block">فيديو المقر</label>

                        @if(($existingMedia['video'] ?? collect())->isNotEmpty())
                            @php $vid = $existingMedia['video']->first(); @endphp
                            <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                                <button type="button"
                                        @click="openVideo('{{ Storage::url($vid->path) }}')"
                                        class="flex items-center gap-2 min-w-0 hover:opacity-70 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                                    </svg>
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $vid->original_name ?? basename($vid->path) }}</span>
                                </button>
                                <button type="button" wire:click="deleteMedia({{ $vid->id }})" wire:confirm="حذف الفيديو؟"
                                        class="{{ $clearBtn }} mr-3">حذف</button>
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <input type="file" wire:model="newVideo" accept="video/mp4,video/avi,video/quicktime" class="{{ $fileInput }} flex-1" />
                                @if($newVideo)
                                    <button type="button" wire:click="clearVideo" class="{{ $clearBtn }}">مسح</button>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-400 mt-1">MP4, AVI, MOV — الحد الأقصى 100 ميجا</p>
                            @error('newVideo') <p class="{{ $err }}">{{ $message }}</p> @enderror
                            @if($newVideo)
                                <p class="text-xs text-[#c9a847] mt-1">✓ {{ $newVideo->getClientOriginalName() }}</p>
                            @endif
                        @endif
                    </div>

                    {{-- ── ملفات أخرى (PDF) ── --}}
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3 block">ملفات أخرى (PDF)</label>

                        @if(($existingMedia['document'] ?? collect())->isNotEmpty())
                            <div class="space-y-2 mb-3">
                                @foreach($existingMedia['document'] as $doc)
                                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                                        <a href="{{ Storage::url($doc->path) }}" target="_blank"
                                           class="flex items-center gap-2 min-w-0 hover:opacity-70 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                            <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $doc->original_name ?? basename($doc->path) }}</span>
                                        </a>
                                        <button type="button" wire:click="deleteMedia({{ $doc->id }})" wire:confirm="حذف هذا الملف؟"
                                                class="{{ $clearBtn }} mr-3">حذف</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-center gap-3">
                            <input type="file" wire:model="newDocuments" multiple accept=".pdf" class="{{ $fileInput }} flex-1" />
                            @if($newDocuments)
                                <button type="button" wire:click="clearDocuments" class="{{ $clearBtn }}">مسح</button>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-400 mt-1">PDF فقط — الحد الأقصى 10 ميجا لكل ملف</p>
                        @error('newDocuments')   <p class="{{ $err }}">{{ $message }}</p> @enderror
                        @error('newDocuments.*') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        @if($newDocuments)
                            <div class="mt-2 space-y-1">
                                @foreach($newDocuments as $doc)
                                    <p class="text-xs text-[#c9a847]">✓ {{ $doc->getClientOriginalName() }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                {{-- ── Lightbox: صور ── --}}
                <div x-show="lightbox"
                     x-transition.opacity
                     @keydown.escape.window="closeAll()"
                     @click.self="closeAll()"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                     style="display:none">
                    <div class="relative max-w-3xl max-h-[90vh] w-full">
                        <img :src="lightboxSrc" class="max-h-[85vh] w-full object-contain rounded-xl" />
                        <button type="button" @click="closeAll()"
                                class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-white text-zinc-800 font-bold text-sm flex items-center justify-center shadow hover:bg-zinc-100 transition">
                            ×
                        </button>
                    </div>
                </div>

                {{-- ── Modal: فيديو ── --}}
                <div x-show="videoModal"
                     x-transition.opacity
                     @keydown.escape.window="closeAll()"
                     @click.self="closeAll()"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                     style="display:none">
                    <div class="relative w-full max-w-3xl">
                        <video x-show="videoModal"
                               :src="videoSrc"
                               controls autoplay
                               class="w-full rounded-xl max-h-[80vh]">
                        </video>
                        <button type="button" @click="closeAll()"
                                class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-white text-zinc-800 font-bold text-sm flex items-center justify-center shadow hover:bg-zinc-100 transition">
                            ×
                        </button>
                    </div>
                </div>

            </div>
