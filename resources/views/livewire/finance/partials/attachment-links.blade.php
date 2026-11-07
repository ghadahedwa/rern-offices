{{-- روابط صور أمر الدفع — الضغط يفتح العارض في الصفحة (`openViewer` في account.blade.php)،
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
<span class="inline-flex flex-wrap gap-1">
    @foreach($order->attachments as $file)
        <a href="{{ $file->url() }}" target="_blank"
           @click.prevent="openViewer(@js($files), {{ $loop->index }})"
           class="inline-flex items-center px-1.5 py-0.5 rounded bg-[#c9a847]/10 text-[#b8962e] hover:bg-[#c9a847]/20 transition"
           title="{{ $file->downloadName() }}">
            {{ $file->isPdf() ? 'PDF' : __('home.fin_image') }} {{ $loop->iteration }}
        </a>
    @endforeach
</span>
