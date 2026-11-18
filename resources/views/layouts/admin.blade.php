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

    @php($pageTitle = match (true) {
        request()->routeIs('admin.dashboard') => 'Dashboard',
        request()->routeIs('notifications.index') => 'Notifications',
        request()->routeIs('admin.settings.*') => 'System Settings',
        request()->routeIs('admin.audit.*') => 'Audit Logs',
        request()->routeIs('admin.finance.overview') => 'Financial Overview',
        request()->routeIs('admin.finance.income') => 'Income Reports',
        request()->routeIs('admin.finance.outstanding') => 'Outstanding Payments',
        request()->routeIs('admin.coaches.create') => 'Register Coach',
        request()->routeIs('admin.coaches.edit') => 'Edit Coach',
        request()->routeIs('admin.coaches.view') => 'Coach Profile',
        request()->routeIs('admin.coaches.*') => 'Coach Management',
        request()->routeIs('admin.students.create') => 'Register Student',
        request()->routeIs('admin.students.edit') => 'Edit Student',
        request()->routeIs('admin.students.view') => 'Student Profile',
        request()->routeIs('admin.students.*') => 'Student Management',
        request()->routeIs('admin.vehicles.create') => 'Add Vehicle',
        request()->routeIs('admin.vehicles.edit') => 'Edit Vehicle',
        request()->routeIs('admin.vehicles.view') => 'Vehicle Profile',
        request()->routeIs('admin.vehicles.expiry') => 'Vehicle Document Expiry',
        request()->routeIs('admin.vehicles.notifications') => 'Vehicle Alert History',
        request()->routeIs('admin.vehicles.*') => 'Vehicle Management',
        request()->routeIs('admin.vehicle-categories.create') => 'Create Vehicle Category',
        request()->routeIs('admin.vehicle-categories.edit') => 'Edit Vehicle Category',
        request()->routeIs('admin.vehicle-categories.view') => 'Vehicle Category Details',
        request()->routeIs('admin.vehicle-categories.*') => 'Vehicle Categories',
        request()->routeIs('admin.payments.create') => 'Record Payment',
        request()->routeIs('admin.payments.receipt') => 'Payment Receipt',
        request()->routeIs('admin.payments.*') => 'Payments',
        request()->routeIs('admin.training.*') => 'Training Management',
        request()->routeIs('admin.trials.create') => 'Schedule Trial',
        request()->routeIs('admin.trials.view') => 'Trial Details',
        request()->routeIs('admin.trials.*') => 'Trial Management',
        default => 'Driving School Management',
    })

    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        <aside class="hidden w-64 bg-slate-950 text-white flex-col fixed inset-y-0 left-0 z-40 lg:flex">

            {{-- Logo --}}
            <div class="h-20 flex items-center px-6 border-b border-white/10">

                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-lg">
                    DS
                </div>

                <div class="ml-3">
                    <h1 class="font-bold text-sm">{{ $schoolSettings->school_name ?: 'Driving School' }}</h1>
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

                        @can('vehicles.view')
                            <a href="{{ route('admin.vehicles.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.vehicles.*') ? 'sidebar-active' : '' }}">
                                <span aria-hidden="true">V</span>
                                <span>Vehicles</span>
                            </a>
                            <a href="{{ route('admin.vehicles.expiry') }}" class="ml-8 block rounded-lg px-3 py-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white {{ request()->routeIs('admin.vehicles.expiry') ? 'bg-white/10 text-white' : '' }}">Document Expiry</a>
                            <a href="{{ route('admin.vehicles.notifications') }}" class="ml-8 block rounded-lg px-3 py-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white {{ request()->routeIs('admin.vehicles.notifications') ? 'bg-white/10 text-white' : '' }}">Expiry Notification History</a>
                        @endcan

                        @can('payments.view')
                            <a href="{{ route('admin.payments.index') }}" class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'sidebar-active' : '' }}">
                                <span aria-hidden="true">$</span>
                                <span>Payments</span>
                            </a>
                        @endcan

                        @can('training.view')
                            <a href="{{ route('admin.training.index') }}" class="sidebar-link {{ request()->routeIs('admin.training.*') ? 'sidebar-active' : '' }}">
                                <span aria-hidden="true">T</span>
                                <span>Training</span>
                            </a>
                        @endcan

                        @can('trials.view')
                            <a href="{{ route('admin.trials.index') }}" class="sidebar-link {{ request()->routeIs('admin.trials.*') ? 'sidebar-active' : '' }}">
                                <span aria-hidden="true">TR</span>
                                <span>Trial Management</span>
                            </a>
                        @endcan

                    </div>

                </div>

                {{-- People --}}
                <div>

                    <p class="sidebar-heading">PEOPLE</p>

                    @can('coaches.view')
                    <a href="{{ route('admin.coaches.index') }}" 
                    class="sidebar-link {{ request()->routeIs('admin.coaches.*') ? 'sidebar-active' : '' }}">
                        <span>♧</span>
                        <span>Coaches</span>
                    </a>
                    @endcan

                </div>

                {{-- Finance --}}
                <div>

                    <p class="sidebar-heading">FINANCE</p>

                    @can('financial-reports.view')
                        <div class="space-y-1">
                            <a href="{{ route('admin.finance.overview') }}" class="sidebar-link {{ request()->routeIs('admin.finance.overview') ? 'sidebar-active' : '' }}"><span aria-hidden="true">LKR</span><span>Financial Overview</span></a>
                            <a href="{{ route('admin.finance.income') }}" class="sidebar-link {{ request()->routeIs('admin.finance.income') ? 'sidebar-active' : '' }}"><span aria-hidden="true">IN</span><span>Income Reports</span></a>
                            <a href="{{ route('admin.finance.outstanding') }}" class="sidebar-link {{ request()->routeIs('admin.finance.outstanding') ? 'sidebar-active' : '' }}"><span aria-hidden="true">OS</span><span>Outstanding Payments</span></a>
                        </div>
                    @endcan

                </div>

                {{-- System --}}
                <div>

                    <p class="sidebar-heading">SYSTEM</p>

                    @can('settings.manage')
                    <a href="{{ route('admin.settings.edit') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'sidebar-active' : '' }}">
                        <span>⚙</span>
                        <span>Settings</span>
                    </a>
                    @endcan
                    @can('audit-logs.view')
                    <a href="{{ route('admin.audit.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit.*') ? 'sidebar-active' : '' }}"><span>LOG</span><span>Audit Logs</span></a>
                    @endcan

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
        <div class="flex-1 ml-0 min-w-0 lg:ml-64">

            {{-- Topbar --}}
            <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 sticky top-0 z-30">

                <div>
                    <h2 class="text-lg font-semibold">{{ $pageTitle }}</h2>
                    <p class="text-xs text-slate-500"><a href="{{ route('admin.dashboard') }}" class="hover:text-blue-700">Dashboard</a> / {{ $pageTitle }}</p>

                    <p class="text-xs text-slate-500">
                        Welcome back, {{ auth()->user()->name }}
                    </p>
                </div>

                <div class="flex items-center gap-5">

                    <span class="text-sm text-slate-500">
                        {{ now()->format(config('school.date_format', 'd M Y')) }}
                    </span>

                    <a href="{{ route('notifications.index') }}" aria-label="Notifications" class="relative flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-lg">&#128276;@if($unreadNotificationCount)<span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif</a>

                    {{-- notification link replaces the former static button --}}

                    {{--
                        <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                            🔔
                        </button>

                    </div> --}}

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="text-sm font-medium text-red-600 hover:text-red-700">
                            Logout
                        </button>
                    </form>

                </div>

            </header>

            <nav class="flex gap-2 overflow-x-auto bg-slate-950 px-4 py-2 text-xs text-white lg:hidden">
                <a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.dashboard') }}">Dashboard</a>
                @can('students.view_all')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.students.index') }}">Students</a>@endcan
                @can('vehicle-categories.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.vehicle-categories.index') }}">Categories</a>@endcan
                @can('vehicles.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.vehicles.index') }}">Vehicles</a>@endcan
                @can('vehicles.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.vehicles.expiry') }}">Document Expiry</a>@endcan
                @can('vehicles.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.vehicles.notifications') }}">Alert History</a>@endcan
                @can('payments.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.payments.index') }}">Payments</a>@endcan
                @can('training.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.training.index') }}">Training</a>@endcan
                @can('trials.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.trials.index') }}">Trials</a>@endcan
                @can('coaches.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.coaches.index') }}">Coaches</a>@endcan
                @can('financial-reports.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.finance.overview') }}">Finance</a>@endcan
                @can('settings.manage')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.settings.edit') }}">Settings</a>@endcan
                @can('audit-logs.view')<a class="whitespace-nowrap rounded-lg px-3 py-2 hover:bg-white/10" href="{{ route('admin.audit.index') }}">Audit</a>@endcan
            </nav>


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
