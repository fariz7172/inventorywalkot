<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Admin Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        base: '#F7F8F0',
                        warm: '#C0E1D2',
                        accent: '#2F2FE4',
                        'accent-dark': '#2020B0',
                        'accent-light': '#5B5BFF',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'card': '0 2px 20px rgba(47,47,228,0.08)',
                        'sidebar': '4px 0 24px rgba(0,0,0,0.06)',
                        'topbar': '0 2px 16px rgba(0,0,0,0.06)',
                    }
                }
            }
        }
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #F7F8F0;
            font-family: 'Inter', sans-serif;
        }

        /* Sidebar */
        #sidebar {
            transition: transform 0.3s cubic-bezier(.4, 0, .2, 1), width 0.3s ease;
            background: linear-gradient(160deg, #C0E1D2 0%, #a8d4c2 100%);
        }

        #sidebar.collapsed {
            width: 72px !important;
        }

        #sidebar.collapsed .nav-label,
        #sidebar.collapsed .logo-text,
        #sidebar.collapsed .user-info,
        #sidebar.collapsed .nav-badge {
            display: none;
        }

        #sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 12px;
        }

        #sidebar.collapsed .nav-item span.icon {
            margin: 0;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #F7F8F0;
        }

        ::-webkit-scrollbar-thumb {
            background: #c9c5f0;
            border-radius: 4px;
        }

        /* Nav active */
        .nav-item.active {
            background: linear-gradient(135deg, #2F2FE4, #5B5BFF);
            color: white !important;
            box-shadow: 0 4px 15px rgba(47, 47, 228, 0.35);
        }

        .nav-item.active svg {
            color: white !important;
        }

        .nav-item.active .nav-label {
            color: white !important;
        }

        /* Nav hover */
        .nav-item:not(.active):hover {
            background: rgba(47, 47, 228, 0.08);
            color: #2F2FE4;
        }

        .nav-item:not(.active):hover svg {
            color: #2F2FE4;
        }

        /* Stat card hover */
        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(47, 47, 228, 0.14);
        }

        /* Mobile overlay */
        #overlay {
            backdrop-filter: blur(2px);
            background: rgba(0, 0, 0, 0.35);
        }

        /* Notification dot pulse */
        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(1.3);
            }
        }

        .pulse-dot {
            animation: pulse-dot 1.8s infinite;
        }

        /* Page transition */
        .page-content {
            animation: fadeSlide 0.3s ease;
        }

        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-base text-gray-800 antialiased">

    <!-- Mobile Overlay -->
    <div id="overlay" class="fixed inset-0 z-30 hidden lg:hidden" onclick="closeSidebar()"></div>

    <div class="flex h-screen overflow-hidden">

        <!-- ===== SIDEBAR ===== -->
        <aside id="sidebar"
            class="fixed lg:relative z-40 flex flex-col w-64 h-full shadow-sidebar -translate-x-full lg:translate-x-0 flex-shrink-0">

            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-warm/60">
                <div class="w-9 h-9 rounded-xl bg-accent flex items-center justify-center flex-shrink-0 shadow-md">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="logo-text">
                    <p class="text-sm font-800 text-gray-900 font-bold leading-tight">AdminPro</p>
                    <p class="text-xs text-gray-500">Management System</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <p class="nav-label text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-3 mb-2">Main
                </p>

                @php
                    $navItems = [
                        [
                            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                            'label' => 'Dashboard',
                            'route' => 'dashboard'
                        ],
                        [
                            'label' => 'Data Barang',
                            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10',
                            'children' => [
                                ['label' => 'Kategori Barang', 'route' => 'category.index', 'role' => 'superadmin'],
                                ['label' => 'Stok Barang', 'route' => 'material.index'],
                                ['label' => 'BAP Barang', 'route' => 'surat-jalan.index'],
                            ]
                        ],
                        [
                            'label' => 'Laporan',
                            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                            'children' => [
                                ['label' => 'Laporan Utama', 'route' => 'reports', 'role' => 'superadmin'],
                                ['label' => 'Laporan Saldo', 'route' => 'laporan.saldo'],
                                ['label' => 'Rekap Barang Masuk', 'route' => 'laporan.barang-masuk'],
                                ['label' => 'Stock Opname', 'route' => 'stock-opname.index'],
                            ]
                        ],
                        [
                            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                            'label' => 'Manajemen User',
                            'route' => 'user.index',
                            'role' => 'superadmin'
                        ],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php
                        $hasChildren = isset($item['children']);
                        $isAuthorized = !isset($item['role']) || auth()->user()->hasRole($item['role']);
                        
                        if ($hasChildren) {
                            $authorizedChildren = array_filter($item['children'], function($child) {
                                return !isset($child['role']) || auth()->user()->hasRole($child['role']);
                            });
                            $isAuthorized = !empty($authorizedChildren);
                        }
                    @endphp

                    @if($isAuthorized)
                        @if($hasChildren)
                            @php
                                $childRoutes = array_column($item['children'], 'route');
                                $isExpanded = false;
                                foreach($childRoutes as $r) { if(request()->routeIs($r)) { $isExpanded = true; break; } }
                            @endphp
                            <div x-data="{ open: {{ $isExpanded ? 'true' : 'false' }} }" class="space-y-1">
                                <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl cursor-pointer transition-all duration-200 text-gray-600 hover:bg-white/50">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}" />
                                        </svg>
                                        <span class="font-medium text-sm">{{ $item['label'] }}</span>
                                    </div>
                                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                <div x-show="open" x-transition:enter="transition ease-out duration-100" 
                                    x-transition:enter-start="opacity-0 transform -translate-y-2" 
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    class="pl-10 space-y-1">
                                    @foreach($item['children'] as $child)
                                        @if(!isset($child['role']) || auth()->user()->hasRole($child['role']))
                                            <a href="{{ route($child['route']) }}" 
                                                class="block px-3 py-2 rounded-lg text-sm transition-all {{ request()->routeIs($child['route']) ? 'text-accent font-bold bg-white/40' : 'text-gray-500 hover:text-gray-900' }}">
                                                {{ $child['label'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ route($item['route']) }}"
                                class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer transition-all duration-200
                                {{ request()->routeIs($item['route']) ? 'active' : 'text-gray-600 hover:bg-white/50' }}">
                                <svg class="icon w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}" />
                                </svg>
                                <span class="font-medium text-sm">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endif
                @endforeach

                <p class="nav-label text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-3 mt-4 mb-2">
                    Sistem</p>

                @php
                    $sysItems = [
                        ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'Pengaturan', 'route' => 'settings'],
                        ['icon' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1', 'label' => 'Keluar', 'route' => 'login'],
                    ];
                @endphp
                @foreach($sysItems as $item)
                    <a href="{{ route($item['route']) }}"
                        class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer transition-all duration-200 text-gray-600">
                        <svg class="icon w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="nav-label text-sm font-medium">{{ $item['label'] }}</span>
                    </a>
                @endforeach

            </nav>

            <!-- User Profile -->
            <div class="px-3 py-4 border-t border-warm/60">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-9 h-9 rounded-xl bg-accent/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-accent font-bold text-sm">A</span>
                    </div>
                    <div class="user-info flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-black text-accent uppercase tracking-widest truncate">
                            {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                        </p>
                    </div>
                    <button
                        class="user-info w-7 h-7 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ===== MAIN AREA ===== -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <!-- TOPBAR -->
            <header class="flex-shrink-0 bg-base/95 backdrop-blur-sm border-b border-warm/60 shadow-topbar z-20">
                <div class="flex items-center justify-between px-4 lg:px-6 h-16 gap-4">

                    <!-- Left: Hamburger + Breadcrumb -->
                    <div class="flex items-center gap-3">
                        <button id="sidebarToggle" onclick="toggleSidebar()"
                            class="w-9 h-9 rounded-xl bg-warm hover:bg-warm/70 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="hidden md:flex items-center gap-2 text-sm">
                            <span class="text-gray-400">Panel</span>
                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="font-semibold text-gray-700">@yield('title', 'Dashboard')</span>
                        </div>
                    </div>

                    <!-- Right: Search, Notif, Avatar -->
                    <div class="flex items-center gap-2 lg:gap-3">

                        <!-- Search -->
                        <div x-data="{ query: '' }"
                            class="hidden md:flex items-center bg-warm/60 rounded-xl px-3 py-2 gap-2 w-52 lg:w-64 focus-within:ring-2 focus-within:ring-accent/20 transition-all">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="query"
                                x-on:input.debounce.300ms="$dispatch('global-search', { search: query })"
                                placeholder="Cari sesuatu..."
                                class="bg-transparent text-sm text-gray-600 placeholder-gray-400 outline-none w-full" />
                        </div>

                        <!-- Notification -->
                        <div class="relative">
                            <button
                                class="w-9 h-9 rounded-xl bg-warm hover:bg-warm/70 flex items-center justify-center transition-colors relative">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span
                                    class="pulse-dot absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-base"></span>
                            </button>
                        </div>

                        <!-- Avatar -->
                        <div class="flex items-center gap-2 cursor-pointer group">
                            <div class="w-9 h-9 rounded-xl bg-accent flex items-center justify-center shadow-md">
                                <span
                                    class="text-white font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <div class="hidden lg:block">
                                <p class="text-sm font-bold text-gray-800 leading-tight">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] font-black text-accent uppercase tracking-widest">
                                    {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="flex-1 overflow-y-auto bg-base">
                <div class="page-content p-4 lg:p-6">
                    @if(isset($slot))
                        {{ $slot }}
                    @endif
                    @yield('content')
                    <livewire:surat-jalan.detail-modal />
                    <livewire:laporan.transaction-detail-modal />
                    <livewire:laporan.riwayat-saldo-modal />
                </div>
            </main>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        let isDesktopCollapsed = false;

        function toggleSidebar() {
            const isLg = window.innerWidth >= 1024;
            if (isLg) {
                isDesktopCollapsed = !isDesktopCollapsed;
                sidebar.classList.toggle('collapsed', isDesktopCollapsed);
            } else {
                const isOpen = !sidebar.classList.contains('-translate-x-full');
                if (isOpen) { closeSidebar(); } else { openSidebar(); }
            }
        }

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    </script>

    @stack('scripts')

    @auth
        @if(session('show_opname_reminder') && auth()->user()->hasRole('gudang') && in_array(date('w'), [5, 6]))
            <script>
                Swal.fire({
                    title: 'Pengingat Penting!',
                    html: 'Jadwal Stok Opname akan diadakan di hari <b style="color: #2F2FE4">Minggu</b>.<br><small style="color: #666">Mohon segera persiapkan data dan barang.</small>',
                    icon: 'warning',
                    confirmButtonText: 'Saya Mengerti',
                    confirmButtonColor: '#2F2FE4',
                    background: '#F7F8F0',
                    allowOutsideClick: false
                });
            </script>
        @endif
    @endauth
</body>

</html>