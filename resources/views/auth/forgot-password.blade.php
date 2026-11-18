<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Driving School Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10">
    <main class="w-full max-w-md rounded-2xl bg-white p-6 shadow-lg sm:p-8">
        <header class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-900">Driving School</h1>
            <p class="mt-2 text-gray-500">Reset your account password</p>
        </header>

        <p class="mb-6 text-sm leading-6 text-gray-600">
            Enter your account email address. If it matches an account, we’ll send you a password reset link.
        </p>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" data-submit-state>
            @csrf

            <div class="mb-6">
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                    placeholder="name@example.com"
                    aria-describedby="email-error"
                >
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                data-loading-label="Sending…"
                class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70"
            >
                Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:underline">Back to Login</a>
        </div>
    </main>
</body>

</html>
