<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ \App\Models\Setting::get('app_name', 'Admin Panel') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/logo.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        base:  '#F3E3D0',
                        warm:  '#F7F8F0',
                        accent: '#2F2FE4',
                        'accent-dark': '#2020B0',
                    },
                    fontFamily: { sans: ['Inter','sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .blob1 {
            position: absolute; width: 400px; height: 400px; border-radius: 50%;
            background: radial-gradient(circle, rgba(47,47,228,0.12) 0%, transparent 70%);
            top: -100px; right: -100px; animation: float 8s ease-in-out infinite;
        }
        .blob2 {
            position: absolute; width: 300px; height: 300px; border-radius: 50%;
            background: radial-gradient(circle, rgba(247,248,240,0.7) 0%, transparent 70%);
            bottom: -80px; left: -80px; animation: float 10s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%,100% { transform: translateY(0px) scale(1); }
            50%      { transform: translateY(-20px) scale(1.05); }
        }
        .card-glass {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(243,227,208,0.8);
        }
        .input-field {
            background: #F7F8F0;
            border: 1.5px solid transparent;
            transition: all 0.2s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: #2F2FE4;
            box-shadow: 0 0 0 3px rgba(47,47,228,0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #2F2FE4, #5B5BFF);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2020B0, #2F2FE4);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(47,47,228,0.35);
        }
        .btn-login:active { transform: translateY(0); }
    </style>
</head>

<body class="min-h-screen bg-base flex flex-col items-center justify-center relative overflow-y-auto py-12 px-4">

    <!-- Decorative Blobs -->
    <div class="blob1"></div>
    <div class="blob2"></div>

    <!-- Decorative Grid -->
    <div class="absolute inset-0 opacity-20"
         style="background-image: radial-gradient(circle, #2F2FE4 1px, transparent 1px);
                background-size: 40px 40px;"></div>

    <!-- Login Card -->
    <div class="relative w-full max-w-md z-10">

        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            @php
                $appLogo = \App\Models\Setting::get('app_logo');
                $appName = \App\Models\Setting::get('app_name', 'InventoryPro');
                $appDesc = \App\Models\Setting::get('app_description', 'Warehouse Management System');
            @endphp
            @if($appLogo)
                <div class="inline-flex w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32 rounded-3xl bg-white items-center justify-center shadow-xl shadow-black/5 mb-6 p-4 border border-gray-100 transition-all hover:scale-105">
                    <img src="{{ asset($appLogo) }}" alt="Logo" class="w-full h-full object-contain">
                </div>
            @else
                <div class="inline-flex w-20 h-20 md:w-24 md:h-24 rounded-3xl bg-accent items-center justify-center shadow-xl shadow-accent/30 mb-6 transition-all hover:scale-105">
                    <svg class="w-10 h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            @endif
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">{{ $appName ?: 'InventoryPro' }}</h1>
            <p class="text-sm md:text-base text-accent font-extrabold mt-2" style="color: #2F2FE4; font-size: 16px;">{{ $appDesc ?: 'Warehouse Management System' }}</p>
        </div>

        <!-- Card -->
        <div class="card-glass rounded-[2rem] shadow-2xl p-8 ring-1 ring-black/5">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">Selamat Datang 👋</h2>
                <p class="text-xs text-gray-500 font-medium mt-1">Silakan masuk untuk mengelola stok gudang.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3 text-red-600 animate-fade-in-up">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <h3 class="text-sm font-bold">Login Gagal</h3>
                        <p class="text-xs mt-1 font-medium">{{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input type="email" name="email" placeholder="admin@panel.com"
                               class="input-field w-full rounded-xl pl-10 pr-4 py-3 text-sm text-gray-700"/>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" name="password" id="passInput" placeholder="••••••••"
                               class="input-field w-full rounded-xl pl-10 pr-12 py-3 text-sm text-gray-700"/>
                        <button type="button" onclick="togglePass()" class="absolute right-3.5 top-1/2 -translate-y-1/2">
                            <svg id="eyeIcon" class="w-4 h-4 text-gray-400 hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 rounded accent-accent">
                        <span class="text-xs text-gray-600 font-medium">Ingat saya</span>
                    </label>
                    <a href="#" class="text-xs font-semibold text-accent hover:text-accent-dark transition-colors">
                        Lupa password?
                    </a>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login w-full py-3 rounded-xl text-white font-semibold text-sm tracking-wide">
                    Masuk ke Dashboard
                </button>

            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'AdminPro') }}. All rights reserved.
        </p>
    </div>

    <script>
        function togglePass() {
            const inp = document.getElementById('passInput');
            inp.type = inp.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
