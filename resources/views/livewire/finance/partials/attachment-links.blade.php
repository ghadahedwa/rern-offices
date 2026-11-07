{{-- صور أمر الدفع مصغّرةً متراكبة — الضغط يفتح العارض في الصفحة (`openViewer` في account.blade.php)،
     والرابط نفسه يبقى صالحاً (الضغط بالزرّ الأوسط يفتح تبويباً). المعاملة: $order --}}
@php
    $files = $order->attachments->map(fn ($file) => [
        'src'      => $file->url(),
        'download' => $file->url().'?download=1',
        'name'     => $file->downloadName(),
        // للعرض بلا امتداد: «1.jpg» داخل سطرٍ عربي تُقلب فتُقرأ «jpg.1»
        'title'    => pathinfo($file->downloadName(), PATHINFO_FILENAME),
        'pdf'      => $file->isPdf(),
    ])->values();
@endphp
<span class="inline-flex shrink-0 -space-x-2 space-x-reverse">
    @foreach($order->attachments as $file)
        <a href="{{ $file->url() }}" target="_blank"
           @click.prevent="openViewer(@js($files), {{ $loop->index }})"
           class="relative block w-9 h-9 rounded-md border-2 border-white dark:border-zinc-900 shadow-sm overflow-hidden bg-zinc-100 dark:bg-zinc-800 hover:z-10 hover:scale-110 transition"
           title="{{ $file->downloadName() }}">
            {{-- الملفات القديمة قبل «صور فقط» قد تكون PDF — لا معاينة لها --}}
            @if($file->isPdf())
                <span class="w-full h-full grid place-items-center text-red-600 font-bold" style="font-size: 0.6rem">PDF</span>
            @else
                <img src="{{ $file->url() }}" alt="" loading="lazy" class="w-full h-full object-cover">
            @endif
        </a>
    @endforeach
</span>
