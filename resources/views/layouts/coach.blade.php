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

    <header class="bg-slate-950 text-white px-4 py-4 flex items-center justify-between sm:px-6">

        <div><h1 class="font-bold">Driving School</h1><a href="{{ route('coach.dashboard') }}" class="text-xs text-slate-300">Coach Training Portal</a><nav class="mt-2 flex gap-4 text-xs">@can('training.view_assigned')<a href="{{ route('coach.training.index') }}" class="text-slate-200 hover:text-white">Students</a>@endcan @can('trials.view_assigned')<a href="{{ route('coach.trials.index') }}" class="text-slate-200 hover:text-white">Trials</a>@endcan</nav></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>

    </header>

    <main class="p-4 sm:p-6">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @livewireScripts

</body>

</html>
