<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>غير مصرح</title>
    @include('partials.head')
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700" rel="stylesheet" />
    <style>
        * { font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif; }
        .dot-grid {
            background-image: radial-gradient(circle, #c9a84755 1.5px, transparent 1.5px);
            background-size: 18px 18px;
        }
    </style>
</head>
<body class="antialiased min-h-screen" style="background-image: url('/images/bg-image.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <div class="fixed inset-0" style="background-color: rgba(255, 255, 255, 0.7);"></div>

    <div class="relative min-h-screen flex flex-col items-center justify-center px-4 overflow-hidden">

        <div class="dot-grid absolute top-4 start-4 w-36 h-36 opacity-70 pointer-events-none"></div>
        <div class="dot-grid absolute bottom-4 end-4 w-36 h-36 opacity-70 pointer-events-none"></div>

        <div class="z-10 flex flex-col items-center gap-6 text-center max-w-md w-full">

            <img src="{{ asset('images/logo3.png') }}" class="w-36 h-36 object-contain" alt="الشعار" />

            <div class="bg-white/90 rounded-2xl shadow-lg px-8 py-10 w-full border border-zinc-200">

                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background-color: #fff3cd;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                             stroke="#c9a847" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                </div>

                <p class="text-6xl font-bold mb-2" style="color: #c9a847;">403</p>
                <h1 class="text-xl font-semibold mb-2" style="color: #2c3e6b;">غير مصرح لك بالدخول</h1>
                <p class="text-zinc-500 text-sm mb-6">غير مصرح لك الدخول لهذه الصفحة</p>

                <a href="{{ url()->previous() === url()->current() ? route('dashboard') : url()->previous() }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-medium transition"
                   style="background-color: #c9a847;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    رجوع
                </a>
            </div>

        </div>

        <footer class="z-10 flex items-center gap-2 text-sm mt-8" style="color: #2c3e6b;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            جميع الحقوق محفوظة &copy; وزارة العدل
        </footer>

    </div>

</body>
</html>
