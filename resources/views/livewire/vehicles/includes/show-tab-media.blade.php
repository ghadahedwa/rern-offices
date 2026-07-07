@php
    $photos    = $vehicle->media->where('type', 'photo');
    $videos    = $vehicle->media->where('type', 'video');
    $documents = $vehicle->media->where('type', 'document');
    $licensePhotos = $vehicle->media->where('type', 'license_photo');
    $photoUrls = $photos->values()->map(fn($p) => asset('storage/' . $p->path))->values()->toArray();
@endphp

<div x-data="{
        photos: @js($photoUrls),
        active: 0,
        viewer: false,
        viewerSrc: '',
        slidePrev() {
            this.active = (this.active - 1 + this.photos.length) % this.photos.length;
        },
        slideNext() {
            this.active = (this.active + 1) % this.photos.length;
        },
        closeViewer() {
            this.$el.querySelectorAll('video').forEach(v => v.pause());
            this.viewer = false;
            this.viewerSrc = '';
        }
     }"
     @keydown.escape.window="closeViewer()"
     @keydown.left.window="!viewer && slidePrev()"
     @keydown.right.window="!viewer && slideNext()">

    {{-- ── صور السيارة ── --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('home.section_vehicle_photos') }}
                <span class="text-xs font-normal text-zinc-400 mr-1">({{ $photos->count() }})</span>
            </h3>
        </div>

        @if($photos->isNotEmpty())
        @php $photoList = $photos->values(); @endphp

        <div class="flex items-center gap-2">
            <button type="button"
                    x-show="photos.length > 1"
                    @click="slidePrev()"
                    class="shrink-0 w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 hover:bg-[#c9a847]/20 dark:hover:bg-[#c9a847]/20 text-zinc-500 hover:text-[#c9a847] flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <div class="flex-1 relative rounded-xl overflow-hidden" style="aspect-ratio:16/9">
                <img :src="photos[active]" class="w-full h-full object-cover" alt="" />
                <div x-show="photos.length > 1"
                     class="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2.5 py-1 rounded-full backdrop-blur-sm">
                    <span x-text="active + 1"></span> / <span x-text="photos.length"></span>
                </div>
            </div>

            <button type="button"
                    x-show="photos.length > 1"
                    @click="slideNext()"
                    class="shrink-0 w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 hover:bg-[#c9a847]/20 dark:hover:bg-[#c9a847]/20 text-zinc-500 hover:text-[#c9a847] flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        @if($photoList->count() > 1)
        <div class="flex gap-2 mt-3 overflow-x-auto pb-1 px-10" style="scrollbar-width:thin;">
            @foreach($photoList as $index => $photo)
            <button type="button"
                    @click="active = {{ $index }}"
                    class="shrink-0 w-16 h-16 rounded-lg overflow-hidden transition-all duration-150"
                    :class="active === {{ $index }}
                        ? 'ring-2 ring-[#c9a847] ring-offset-1 opacity-100'
                        : 'opacity-50 hover:opacity-80'">
                <img src="{{ asset('storage/' . $photo->path) }}" class="w-full h-full object-cover" alt="" />
            </button>
            @endforeach
        </div>
        @endif

        @else
        <div class="flex items-center justify-center h-28 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm">
            {{ __('home.no_photos') }}
        </div>
        @endif
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-700 mb-6"></div>

    {{-- ── فيديو السيارة ── --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_vehicle_video') }}</h3>
        </div>
        @if($videos->isNotEmpty())
        @foreach($videos as $vid)
        <div class="flex items-center p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
            <button type="button"
                    @click="viewerSrc='{{ asset('storage/' . $vid->path) }}'; viewer=true"
                    class="flex items-center gap-3 min-w-0 hover:opacity-70 transition">
                <div class="w-10 h-10 rounded-lg bg-[#c9a847]/10 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#c9a847]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $vid->original_name ?? basename($vid->path) }}</p>
                    <p class="text-xs text-zinc-400">{{ __('home.play_video') }}</p>
                </div>
            </button>
        </div>
        @endforeach
        @else
        <div class="flex items-center justify-center h-20 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm">
            {{ __('home.no_video') }}
        </div>
        @endif
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-700 mb-6"></div>

    {{-- ── قرار الإنشاء ── --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_establishment_decision') }}</h3>
        </div>
        @if($documents->isNotEmpty())
        <div class="space-y-2">
            @foreach($documents as $doc)
            <a href="{{ asset('storage/' . $doc->path) }}" target="_blank"
               class="flex items-center gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $doc->original_name ?? basename($doc->path) }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-400 mr-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </a>
            @endforeach
        </div>
        @else
        <div class="flex items-center justify-center h-20 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm">
            {{ __('home.no_establishment_decision') }}
        </div>
        @endif
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-700 mb-6"></div>

    {{-- ── صورة الرخصة ── --}}
    <div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-1 h-5 bg-[#c9a847] rounded-full"></div>
            <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">{{ __('home.section_license_photo') }}</h3>
        </div>
        @if($licensePhotos->isNotEmpty())
        @foreach($licensePhotos as $lic)
        <img src="{{ asset('storage/' . $lic->path) }}"
             alt=""
             @click="viewerSrc='{{ asset('storage/' . $lic->path) }}'; viewer=true"
             style="width:96px;height:96px;object-fit:cover;"
             class="rounded-lg border-2 border-[#c9a847] cursor-pointer transition-transform duration-150 hover:opacity-95 active:scale-90" />
        @endforeach
        @else
        <div class="flex items-center justify-center h-20 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-zinc-400 text-sm">
            {{ __('home.no_license_photo') }}
        </div>
        @endif
    </div>

    {{-- ── Video / Photo Fullscreen Viewer ── --}}
    <div x-show="viewer"
         x-transition.opacity
         @click.self="closeViewer()"
         class="fixed inset-0 flex flex-col bg-black/95"
         style="display:none; z-index:9999;">
        <div class="flex items-center h-14 shrink-0 px-4">
            <button type="button" @click="closeViewer()"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white transition text-sm font-semibold shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ __('home.close') }}
            </button>
        </div>
        <div class="flex-1 min-h-0 flex items-center justify-center px-4 pb-4" @click.self="closeViewer()">
            <template x-if="viewer && viewerSrc.match(/\.(mp4|webm|mov|avi)$/i)">
                <video :src="viewerSrc" controls autoplay class="rounded-xl shadow-2xl" style="max-width: 100%; max-height: calc(100vh - 80px);"></video>
            </template>
            <template x-if="viewer && !viewerSrc.match(/\.(mp4|webm|mov|avi)$/i)">
                <img :src="viewerSrc" class="rounded-xl shadow-2xl" style="max-width: 100%; max-height: calc(100vh - 80px); object-fit:contain;" />
            </template>
        </div>
    </div>

</div>
