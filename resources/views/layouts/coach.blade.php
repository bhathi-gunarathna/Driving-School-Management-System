<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Coach Dashboard')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="bg-slate-50 min-h-screen">

    <header class="bg-slate-950 text-white px-4 py-4 flex flex-wrap items-center justify-between gap-4 sm:px-6">

        <div class="min-w-0"><h1 class="font-bold">{{ $schoolSettings->school_name ?: 'Driving School' }}</h1><a href="{{ route('coach.dashboard') }}" class="text-xs text-slate-300">Coach Training Portal</a><nav class="mt-2 flex flex-wrap gap-4 text-xs">@can('training.view_assigned')<a href="{{ route('coach.training.index') }}" class="text-slate-200 hover:text-white">Students</a>@endcan @can('trials.view_assigned')<a href="{{ route('coach.trials.index') }}" class="text-slate-200 hover:text-white">Trials</a>@endcan</nav></div>

        <div class="flex items-center gap-3"><a href="{{ route('notifications.index') }}" class="relative rounded-lg bg-white/10 px-3 py-2 text-sm">Notifications @if($unreadNotificationCount)<span class="ml-1 rounded-full bg-red-500 px-2 py-0.5 text-xs">{{ $unreadNotificationCount }}</span>@endif</a><form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form></div>

    </header>

    <nav aria-label="Breadcrumb" class="bg-white px-4 py-2 text-xs text-slate-500 sm:px-6"><a href="{{ route('coach.dashboard') }}" class="hover:text-blue-700">Dashboard</a> / {{ request()->routeIs('notifications.index') ? 'Notifications' : (request()->routeIs('coach.trials.*') ? 'Trials' : (request()->routeIs('coach.training.*') ? 'Training' : 'Dashboard')) }}</nav>

    <main class="p-4 sm:p-6">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts

</body>

</html>
