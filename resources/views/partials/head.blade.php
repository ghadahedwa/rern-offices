<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.__('home.app_name') : __('home.app_name') }}
</title>

<link rel="icon" href="/images/logo2.png" type="image/png">

{{-- تثبيت المنظومة كتطبيق (PWA). المتصفح لا يعرض التثبيت ولا يشغّل الـService Worker إلا على HTTPS،
     فعلى HTTP هذه السطور بلا أثر. --}}
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#c9a847">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ __('home.pwa_short_name') }}">
{{-- آيفون يملأ الشفافية بالأسود — أيقونته على خلفية بيضاء. --}}
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<script data-navigate-once>
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
    // يُلتقط هنا لا في app.js: قد يُطلقه المتصفح قبل تحميل الحزمة فيضيع. يقرؤه زرّ «تثبيت التطبيق» (resources/js/pwa-install.js).
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.__pwaDeferred = e;
        window.dispatchEvent(new Event('pwa:installable'));
    });
</script>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
