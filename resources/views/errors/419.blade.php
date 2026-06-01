<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('home.app_name') }}</title>
    @include('partials.head')
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700" rel="stylesheet" />
    <style>* { font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif; }</style>
</head>
<body class="antialiased min-h-screen" style="background-image: url('/images/bg-image.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="fixed inset-0" style="background-color: rgba(255, 255, 255, 0.7);"></div>
    <div class="relative min-h-screen flex flex-col items-center justify-center px-4">
        <div class="z-10 flex flex-col items-center gap-6 text-center max-w-md w-full">
            <img src="{{ asset('images/logo3.png') }}" class="w-36 h-36 object-contain" alt="الشعار" />
            <div class="bg-white/90 rounded-2xl shadow-lg px-8 py-10 w-full border border-zinc-200">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background-color: #fff3cd;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="#c9a847" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-6xl font-bold mb-2" style="color: #c9a847;">419</p>
                <h1 class="text-xl font-semibold mb-2" style="color: #2c3e6b;">انتهت صلاحية الجلسة</h1>
                <p class="text-zinc-500 text-sm mb-6">انتهت صلاحية جلستك، يرجى تحديث الصفحة وإعادة المحاولة</p>
                <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-medium transition" style="background-color: #c9a847;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    تحديث الصفحة
                </a>
            </div>
        </div>
        <footer class="z-10 flex items-center gap-2 text-sm mt-8" style="color: #2c3e6b;">
            جميع الحقوق محفوظة &copy; وزارة العدل
        </footer>
    </div>
</body>
</html>
