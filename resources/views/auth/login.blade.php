<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Admin Panel</title>
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

<body class="min-h-screen bg-base flex items-center justify-center relative overflow-hidden p-4">

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
            <div class="inline-flex w-16 h-16 rounded-2xl bg-accent items-center justify-center shadow-lg shadow-accent/30 mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Inventory<span class="text-accent">Pro</span></h1>
            <p class="text-sm text-gray-500 font-medium">Warehouse Management System</p>
        </div>

        <!-- Card -->
        <div class="card-glass rounded-[2rem] shadow-2xl p-8 ring-1 ring-black/5">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">Selamat Datang 👋</h2>
                <p class="text-xs text-gray-500 font-medium mt-1">Silakan masuk untuk mengelola stok gudang.</p>
            </div>

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

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-warm/60"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-4 text-xs text-gray-400">atau masuk dengan</span>
                    </div>
                </div>

                <!-- Social Login -->
                <div class="grid grid-cols-2 gap-3">
                    <button type="button"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-xl border border-warm bg-warm/30 hover:bg-warm/60 transition-colors text-sm font-medium text-gray-600">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Google
                    </button>
                    <button type="button"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-xl border border-warm bg-warm/30 hover:bg-warm/60 transition-colors text-sm font-medium text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        GitHub
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} AdminPro. All rights reserved.
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
