<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('partials.head')
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700" rel="stylesheet" />
        <style>
            * { font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif; }
            .dot-grid {
                background-image: radial-gradient(circle, #c9a84755 1.5px, transparent 1.5px);
                background-size: 18px 18px;
            }
            input:focus { outline: none; box-shadow: 0 0 0 2px #c9a84766; border-color: #c9a847 !important; }
            input[type="checkbox"]:focus { box-shadow: 0 0 0 2px #c9a84766; }
        </style>
    </head>
    <body class="antialiased min-h-screen" style="background-image: url('/images/bg-image.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        {{-- Background overlay --}}
        <div class="fixed inset-0" style="background-color: rgba(255, 255, 255, 0.7);"></div>

        <div class="relative min-h-screen flex flex-col items-center py-6 px-4 overflow-hidden">

            {{-- Dot grid decoration: top-start corner --}}
            <div class="dot-grid absolute top-4 start-4 w-36 h-36 opacity-70 pointer-events-none"></div>
            {{-- Dot grid decoration: bottom-end corner --}}
            <div class="dot-grid absolute bottom-4 end-4 w-36 h-36 opacity-70 pointer-events-none"></div>

            {{-- Main content --}}
            <div class="flex flex-col items-center w-full max-w-xl z-10 gap-4 flex-1 justify-center">

                <img src="{{ asset('images/logo3.png') }}" class="w-58 h-58 object-contain" alt="الشعار" />

                {{-- Page title --}}
                <p class="text-xl text-center" style="color: #2c3e6b;">{{ __('home.app_name') }}</p>

                {{-- Card slot --}}
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <footer class="z-10 flex items-center gap-2 text-sm mt-6" style="color: #2c3e6b;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                جميع الحقوق محفوظة &copy; وزارة العدل
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
