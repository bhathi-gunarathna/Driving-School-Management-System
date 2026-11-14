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

    <header class="bg-slate-950 text-white px-6 py-5 flex justify-between">

        <h1 class="font-bold">Driving School | Coach Portal</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>

    </header>

    <main class="p-6">
        @yield('content')
    </main>

    @livewireScripts

</body>

</html>