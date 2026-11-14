<div class="space-y-7">

    {{-- Page Header --}}
    <div class="flex items-center gap-4">

        <a href="{{ route('admin.coaches.index') }}"
           class="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50">

            ←

        </a>

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Add New Coach
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Register a new driving instructor to your school.
            </p>
        </div>

    </div>


    {{-- Success Message --}}
    @if (session()->has('success'))

        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl">
            {{ session('success') }}
        </div>

    @endif


    {{-- Registration Form --}}
    <form wire:submit="createCoach">

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

            {{-- Form Header --}}
            <div class="p-6 border-b border-slate-200">

                <h2 class="text-lg font-bold text-slate-900">
                    Coach Information
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Enter the personal and account details of the coach.
                </p>

            </div>


            <div class="p-6 space-y-8">

                {{-- Personal Information --}}
                <div>

                    <h3 class="text-sm font-bold text-slate-800 mb-5">
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Full Name --}}
                        <div>

                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                wire:model="name"
                                placeholder="Enter full name"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >

                            @error('name')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- Phone --}}
                        <div>

                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Contact Number <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="tel"
                                wire:model="phone"
                                placeholder="07XXXXXXXX"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >

                            @error('phone')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- Account Information --}}
                <div class="border-t border-slate-100 pt-7">

                    <h3 class="text-sm font-bold text-slate-800 mb-5">
                        Account Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Email --}}
                        <div class="md:col-span-2">

                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="email"
                                wire:model="email"
                                placeholder="coach@example.com"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >

                            @error('email')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- Password --}}
                        <div>

                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Password <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="password"
                                wire:model="password"
                                placeholder="Minimum 8 characters"
                                autocomplete="new-password"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >

                            @error('password')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror

                        </div>


                        {{-- Confirm Password --}}
                        <div>

                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="password"
                                wire:model="password_confirmation"
                                placeholder="Re-enter password"
                                autocomplete="new-password"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            >

                        </div>

                    </div>

                </div>


                {{-- Account Status --}}
                <div class="border-t border-slate-100 pt-7">

                    <h3 class="text-sm font-bold text-slate-800 mb-5">
                        Account Status
                    </h3>

                    <label class="flex items-start gap-3 cursor-pointer">

                        <input
                            type="checkbox"
                            wire:model="is_active"
                            class="mt-1 w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >

                        <div>

                            <p class="text-sm font-medium text-slate-800">
                                Active Account
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                Allow this coach to access the system.
                            </p>

                        </div>

                    </label>

                </div>

            </div>


            {{-- Form Footer --}}
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex flex-col-reverse sm:flex-row justify-end gap-3">

                <a href="{{ route('admin.coaches.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold text-center hover:bg-slate-100">

                    Cancel

                </a>


                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="createCoach"
                    class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold disabled:opacity-50">

                    <span wire:loading.remove wire:target="createCoach">
                        Register Coach
                    </span>

                    <span wire:loading wire:target="createCoach">
                        Registering...
                    </span>

                </button>

            </div>

        </div>

    </form>

</div>