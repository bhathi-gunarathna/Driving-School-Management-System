<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Driving School Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10">
    <main class="w-full max-w-md rounded-2xl bg-white p-6 shadow-lg sm:p-8">
        <header class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-900">Driving School</h1>
            <p class="mt-2 text-gray-500">Choose a new password</p>
        </header>

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" data-submit-state>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-5">
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="password" class="mb-2 block text-sm font-medium text-gray-700">New password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Confirm new password</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
            </div>

            <button
                type="submit"
                data-loading-label="Resetting…"
                class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70"
            >
                Reset Password
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:underline">Back to Login</a>
        </div>
    </main>
</body>

</html>
