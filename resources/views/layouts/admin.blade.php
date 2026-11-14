<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') | Driving School Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="bg-slate-50 text-slate-800 antialiased">

    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        <aside class="w-64 bg-slate-950 text-white flex flex-col fixed inset-y-0 left-0 z-40">

            {{-- Logo --}}
            <div class="h-20 flex items-center px-6 border-b border-white/10">

                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-lg">
                    DS
                </div>

                <div class="ml-3">
                    <h1 class="font-bold text-sm">Driving School</h1>
                    <p class="text-xs text-slate-400">Management System</p>
                </div>

            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-7 overflow-y-auto">

                {{-- Overview --}}
                <div>

                    <p class="sidebar-heading">OVERVIEW</p>

                    <a href="{{ route('admin.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'sidebar-active' : '' }}">

                        <span>▦</span>
                        <span>Dashboard</span>

                    </a>

                </div>

                {{-- Management --}}
                <div>

                    <p class="sidebar-heading">MANAGEMENT</p>

                    <div class="space-y-1">

                        @can('students.view_all')
                            <a href="{{ route('admin.students.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.students.*') ? 'sidebar-active' : '' }}">
                                <span aria-hidden="true">♙</span>
                                <span>Students</span>
                            </a>
                        @endcan

                        @can('vehicle-categories.view')
                            <a href="{{ route('admin.vehicle-categories.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.vehicle-categories.*') ? 'sidebar-active' : '' }}">
                                <span aria-hidden="true">▤</span>
                                <span>Vehicle Categories</span>
                            </a>
                        @endcan

                        <a href="#" class="sidebar-link">
                            <span>▣</span>
                            <span>Payments</span>
                        </a>

                        <a href="#" class="sidebar-link">
                            <span>◷</span>
                            <span>Training</span>
                        </a>

                        <a href="#" class="sidebar-link">
                            <span>▣</span>
                            <span>Trial Management</span>
                        </a>

                    </div>

                </div>

                {{-- People --}}
                <div>

                    <p class="sidebar-heading">PEOPLE</p>

                    <a href="{{ route('admin.coaches.index') }}" 
                    class="sidebar-link {{ request()->routeIs('admin.coaches.*') ? 'sidebar-active' : '' }}">
                        <span>♧</span>
                        <span>Coaches</span>
                    </a>

                </div>

                {{-- Finance --}}
                <div>

                    <p class="sidebar-heading">FINANCE</p>

                    <a href="#" class="sidebar-link">
                        <span>▤</span>
                        <span>Financial Overview</span>
                    </a>

                    <a href="#" class="sidebar-link">
                        <span>▧</span>
                        <span>Income Reports</span>
                    </a>

                </div>

                {{-- System --}}
                <div>

                    <p class="sidebar-heading">SYSTEM</p>

                    <a href="#" class="sidebar-link">
                        <span>⚙</span>
                        <span>Settings</span>
                    </a>

                </div>

            </nav>

            {{-- Sidebar Footer --}}
            <div class="p-4 border-t border-white/10">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center font-semibold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="flex-1 min-w-0">

                        <p class="text-sm font-medium truncate">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-xs text-slate-400">
                            Owner Account
                        </p>

                    </div>

                </div>

            </div>

        </aside>


        {{-- Main Content --}}
        <div class="flex-1 ml-64 min-w-0">

            {{-- Topbar --}}
            <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-30">

                <div>
                    <h2 class="text-lg font-semibold">
                        @yield('page-heading', 'Dashboard')
                    </h2>

                    <p class="text-xs text-slate-500">
                        Welcome back, {{ auth()->user()->name }}
                    </p>
                </div>

                <div class="flex items-center gap-5">

                    <span class="text-sm text-slate-500">
                        {{ now()->format('d M Y') }}
                    </span>

                    <div class="relative">

                        <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                            🔔
                        </button>

                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="text-sm font-medium text-red-600 hover:text-red-700">
                            Logout
                        </button>
                    </form>

                </div>

            </header>


            {{-- Page Content --}}
            <main class="flex-1 p-6 md:p-8">

                {{ $slot }}

                @yield('content')

            </main>

        </div>

    </div>

    @livewireScripts

</body>
</html>