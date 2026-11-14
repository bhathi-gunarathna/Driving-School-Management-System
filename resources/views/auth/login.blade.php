<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Driving School Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">

        <div class="text-center mb-8">

            <h1 class="text-2xl font-bold text-gray-900">
                Driving School
            </h1>

            <p class="text-gray-500 mt-2">
                Management System
            </p>

        </div>

        <form method="POST" action="{{ route('login') }}">

            @csrf

            <div class="mb-5">

                <label class="block text-sm font-medium mb-2">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                    placeholder="Enter your email"
                >

                @error('email')
                    <p class="text-red-500 text-sm mt-2">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="mb-5">

                <label class="block text-sm font-medium mb-2">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
                    placeholder="Enter your password"
                >

                @error('password')
                    <p class="text-red-500 text-sm mt-2">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="flex items-center justify-between mb-6">

                <label class="flex items-center gap-2 text-sm">

                    <input
                        type="checkbox"
                        name="remember"
                        class="rounded border-gray-300"
                    >

                    Remember me

                </label>

                <a
                    href="{{ route('password.request') }}"
                    class="text-sm text-blue-600 hover:underline"
                >
                    Forgot Password?
                </a>

            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition"
            >
                Sign In
            </button>

        </form>

    </div>

</body>
</html>