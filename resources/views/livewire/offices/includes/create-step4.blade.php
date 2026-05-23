
            {{-- ── Step 5: الوسائط ── --}}
            <div class="mb-1" x-data="{
                modal: null,
                fileName: '',
                uploading: false,
                viewer: null,
                viewerSrc: '',
                viewerType: '',
                deleteId: null,
                deleteLabel: '',
                openModal(name)  { this.modal = name; this.fileName = ''; this.uploading = false; },
                closeModal()     { this.modal = null; this.fileName = ''; this.uploading = false; },
                openViewer(src, type) { this.viewerSrc = src; this.viewerType = type; this.viewer = true; },
                closeViewer()    { this.viewer = false; this.$nextTick(() => { this.viewerSrc = ''; }); },
                askDelete(id, label) { this.deleteId = id; this.deleteLabel = label; },
                confirmDelete()  { $wire.deleteMedia(this.deleteId); this.deleteId = null; this.deleteLabel = ''; },
                init() {
                    $wire.on('mediaUploaded', () => this.closeModal());
                    $el.addEventListener('livewire-upload-start',  () => { this.uploading = true; });
                    $el.addEventListener('livewire-upload-finish', () => { this.uploading = false; });
                    $el.addEventListener('livewire-upload-error',  () => { this.uploading = false; this.fileName = ''; });
                }
            }" @keydown.escape.window="closeModal(); closeViewer(); deleteId = null;">

                {{-- Header --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">صور المقر</h3>
                </div>

                <div class="space-y-8">

                    {{-- ══════════════════════════════════
                         قسم الصور
                    ══════════════════════════════════ --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                صور المقر
                                <span class="text-xs font-normal text-zinc-400 mr-1">({{ ($existingMedia['photo'] ?? collect())->count() }} / 10)</span>
                            </span>
                            @if(($existingMedia['photo'] ?? collect())->count() < 10)
                                <button type="button" @click="openModal('photo')"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-[#c9a847]/10 text-[#b8962e] hover:bg-[#c9a847]/20 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    إضافة صورة
                                </button>
                            @endif
                        </div>

                        @if(($existingMedia['photo'] ?? collect())->isNotEmpty())
                            <table dir="rtl" class="w-full border-collapse">
                                <tr class="flex flex-wrap items-start border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                @foreach($existingMedia['photo'] as $photo)
                                         <td class="py-2 pl-2 text-center" style="width:72px;">
                                            <img src="{{ asset('storage/' . $photo->path) }}"
                                                 alt=""
                                                 @click="openViewer('{{ asset('storage/' . $photo->path) }}', 'photo')"
                                                 style="width:64px;height:64px;min-width:64px;object-fit:cover;"
                                                 class="rounded-lg border-2 border-[#c9a847] cursor-pointer transition-transform duration-150 hover:opacity-95 active:scale-90" />
                                        <br>
                                            <button type="button"
                                                    @click="askDelete({{ $photo->id }}, 'هذه الصورة')"
                                                    class="text-xs text-red-500 hover:text-red-700 font-medium transition whitespace-nowrap cursor-pointer">
                                                حذف
                                            </button>
                                        </td>
                                @endforeach
                                </tr>
                            </table>
                        @else
                            <div class="flex flex-col items-center justify-center h-24 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M21 3.75H3A2.25 2.25 0 00.75 6v12A2.25 2.25 0 003 20.25h18A2.25 2.25 0 0023.25 18V6A2.25 2.25 0 0021 3.75z"/>
                                </svg>
                                لا توجد صور
                            </div>
                        @endif
                    </div>
                </div>

                <br>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">فيديو المقر</h3>
                </div>

                <div class="space-y-8">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">فيديو المقر
                                <span class="text-xs font-normal text-zinc-400 mr-1">({{ ($existingMedia['video'] ?? collect())->count() }} / 1)</span>
                            </span>
                            @if(($existingMedia['video'] ?? collect())->count() < 1)
                                <button type="button" @click="openModal('video')"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-[#c9a847]/10 text-[#b8962e] hover:bg-[#c9a847]/20 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    إضافة فيديو
                                </button>
                            @endif
                        </div>

                        @if(($existingMedia['video'] ?? collect())->isNotEmpty())
                            <table dir="rtl" class="w-full border-collapse">
                                <tr class="flex flex-wrap items-start border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                @foreach($existingMedia['video'] as $vid)
                                         <td class="py-2 pl-2 text-center" style="width:250px;">
                                            <div class="flex items-center justify-between p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                                                <button type="button" @click="openViewer('{{ asset('storage/' . $vid->path) }}', 'video')"
                                                        class="flex items-center gap-3 min-w-0 hover:opacity-70 transition">
                                                    <div class="w-10 h-10 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $vid->original_name ?? basename($vid->path) }}</p>
                                                        <p class="text-xs text-zinc-400">اضغط للتشغيل</p>
                                                    </div>
                                                </button>
                                                <button type="button" @click="askDelete({{ $vid->id }}, 'هذا الفيديو')"
                                                        class="text-xs text-red-500 hover:text-red-700 transition font-medium shrink-0 mr-3 cursor-pointer">حذف</button>
                                            </div>
                                        </td>
                                @endforeach
                                </tr>
                            </table>
                        @else
                            <div class="flex flex-col items-center justify-center h-24 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                لا يوجد فيديو
                            </div>
                        @endif
                    </div>
                </div>

                <br>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
                    <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">ملفات سند الملكيه أو الحيازه وقرار الإنشاء</h3>
                </div>

                <div class="space-y-8">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                ملفات PDF
                                <span class="text-xs font-normal text-zinc-400 mr-1">({{ ($existingMedia['document'] ?? collect())->count() }} / 1)</span>
                            </span>
                            @if(($existingMedia['document'] ?? collect())->count() < 1)
                            <button type="button" @click="openModal('document')"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-[#c9a847]/10 text-[#b8962e] hover:bg-[#c9a847]/20 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                إضافة ملف
                            </button>
                            @endif
                        </div>

                        @if(($existingMedia['document'] ?? collect())->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($existingMedia['document'] as $doc)
                                    <div class="flex items-center justify-between p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                                        <a href="{{ asset('storage/' . $doc->path) }}" target="_blank"
                                           class="flex items-center gap-3 min-w-0 hover:opacity-70 transition">
                                            <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                                </svg>
                                            </div>
                                            <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $doc->original_name ?? basename($doc->path) }}</span>
                                        </a>
                                        <button type="button" @click="askDelete({{ $doc->id }}, 'هذا الملف')"
                                                class="text-xs text-red-500 hover:text-red-700 transition font-medium shrink-0 mr-3 cursor-pointer">حذف</button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-24 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                                لا توجد ملفات
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Upload Modal --}}
                <div x-show="modal !== null"
                     x-transition.opacity
                     @click.self="closeModal()"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                     style="display:none">
                    <div x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-[#c9a847]">
                        <div class="flex items-center justify-between px-5 py-3.5 bg-[#c9a847]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                                    <svg x-show="modal === 'photo'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M21 3.75H3A2.25 2.25 0 00.75 6v12A2.25 2.25 0 003 20.25h18A2.25 2.25 0 0023.25 18V6A2.25 2.25 0 0021 3.75z"/></svg>
                                    <svg x-show="modal === 'video'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                                    <svg x-show="modal === 'document'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                </div>
                                <h3 class="text-sm font-semibold text-white">
                                    <span x-show="modal === 'photo'">إضافة صورة</span>
                                    <span x-show="modal === 'video'">إضافة فيديو</span>
                                    <span x-show="modal === 'document'">إضافة ملف PDF</span>
                                </h3>
                            </div>
                            <button type="button" @click="closeModal(); fileName = ''"
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
                            <div class="rounded-xl border-8 border-dashed border-zinc-200 dark:border-zinc-700 p-5 flex flex-col items-center gap-3 text-center">
                                <div class="w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <svg x-show="modal === 'photo'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M21 3.75H3A2.25 2.25 0 00.75 6v12A2.25 2.25 0 003 20.25h18A2.25 2.25 0 0023.25 18V6A2.25 2.25 0 0021 3.75z"/></svg>
                                    <svg x-show="modal === 'video'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                                    <svg x-show="modal === 'document'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                </div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 min-h-5">
                                    <span x-show="!fileName" class="text-zinc-400">
                                        <span x-show="modal === 'photo'">JPG, PNG, WEBP — max 5MB</span>
                                        <span x-show="modal === 'video'">MP4, AVI, MOV — max 100MB</span>
                                        <span x-show="modal === 'document'">PDF — max 10MB</span>
                                    </span>
                                    <span x-show="fileName" x-text="fileName" class="text-[#b8962e] font-medium break-all"></span>
                                </p>
                                <label x-show="modal === 'photo'" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#b8962e] text-sm font-medium hover:bg-[#c9a847]/10 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    استعراض الملفات
                                    <input type="file" class="hidden" accept="image/*" wire:model="newPhoto" @change="fileName = $event.target.files[0]?.name ?? ''" />
                                </label>
                                <label x-show="modal === 'video'" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#b8962e] text-sm font-medium hover:bg-[#c9a847]/10 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    استعراض الملفات
                                    <input type="file" class="hidden" accept="video/mp4,video/avi,video/quicktime,video/webm" wire:model="newVideo" @change="fileName = $event.target.files[0]?.name ?? ''" />
                                </label>
                                <label x-show="modal === 'document'" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#c9a847] text-[#b8962e] text-sm font-medium hover:bg-[#c9a847]/10 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    استعراض الملفات
                                    <input type="file" class="hidden" accept="application/pdf,.pdf" wire:model="newDocument" @change="fileName = $event.target.files[0]?.name ?? ''" />
                                </label>
                            </div>
                            @error('newPhoto')    <p class="text-xs text-red-500 text-center -mt-2">{{ $message }}</p> @enderror
                            @error('newVideo')    <p class="text-xs text-red-500 text-center -mt-2">{{ $message }}</p> @enderror
                            @error('newDocument') <p class="text-xs text-red-500 text-center -mt-2">{{ $message }}</p> @enderror
                            <div class="flex gap-3">
                                <button type="button"
                                        :disabled="!fileName || uploading"
                                        :class="(fileName && !uploading) ? 'bg-[#c9a847] hover:bg-[#b8962e] text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 cursor-not-allowed'"
                                        class="flex-1 text-sm font-medium py-2.5 rounded-lg transition"
                                        @click="modal === 'photo' ? $wire.uploadPhoto() : (modal === 'video' ? $wire.uploadVideo() : $wire.uploadDocument())">
                                    <span x-show="uploading">جاري تحميل الملف...</span>
                                    <span x-show="!uploading" wire:loading.remove wire:target="uploadPhoto,uploadVideo,uploadDocument">رفع الملف</span>
                                    <span x-show="!uploading" wire:loading wire:target="uploadPhoto,uploadVideo,uploadDocument">جاري الرفع...</span>
                                </button>
                                <button type="button" @click="closeModal()"
                                        class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Viewer Modal --}}
                <div x-show="viewer"
                     x-transition.opacity
                     @click.self="closeViewer()"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4"
                     style="display:none">
                    <div class="relative w-full max-w-3xl">
                        <template x-if="viewerType === 'photo'">
                            <img :src="viewerSrc" class="max-h-[85vh] w-full object-contain rounded-xl" />
                        </template>
                        <template x-if="viewerType === 'video'">
                            <video :src="viewerSrc" controls autoplay class="w-full rounded-xl max-h-[80vh]"></video>
                        </template>
                        <button type="button" @click="closeViewer()"
                                class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-white text-zinc-800 font-bold flex items-center justify-center shadow hover:bg-zinc-100 transition text-base leading-none">×</button>
                    </div>
                </div>

                {{-- Delete Confirmation Modal (موحّد) --}}
                <div x-show="deleteId !== null"
                     x-transition.opacity
                     @click.self="deleteId = null"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                     style="display:none">
                    <div x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-red-500">

                        <div class="flex items-center justify-between px-5 py-3.5 bg-red-500">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-white">تأكيد الحذف</h3>
                            </div>
                            <button type="button" @click="deleteId = null"
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition text-base leading-none">×</button>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 px-5 py-6 space-y-5">
                            <p class="text-sm text-zinc-600 dark:text-zinc-300 text-center">
                                هل أنت متأكد من حذف <span class="font-semibold text-zinc-800 dark:text-zinc-100" x-text="deleteLabel"></span>؟
                            </p>
                            <div class="flex gap-3">
                                <button type="button" @click="confirmDelete()"
                                        class="flex-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2.5 rounded-lg transition">
                                    حذف
                                </button>
                                <button type="button" @click="deleteId = null"
                                        class="flex-1 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
