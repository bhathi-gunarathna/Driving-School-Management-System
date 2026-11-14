<div class="space-y-7">

    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Coaches
            </h1>

            <p class="text-sm text-slate-500 mt-1">
                Manage your driving school instructors and their accounts.
            </p>
        </div>

        @if (auth()->user()->can('coaches.manage'))
        <a href="{{ route('admin.coaches.create') }}"
            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl text-sm font-semibold transition">

            <span class="text-lg">+</span>
            Add New Coach

        </a>
        @endif

    </div>


    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">

        {{-- Total Coaches --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex justify-between items-center">

                <p class="text-sm font-medium text-slate-500">
                    Total Coaches
                </p>

                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    ♙
                </div>

            </div>

            <h2 class="text-3xl font-bold text-slate-900 mt-5">
                {{ $totalCoaches }}
            </h2>

            <p class="text-xs text-slate-400 mt-2">
                All registered coaches
            </p>

        </div>


        {{-- Active Coaches --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex justify-between items-center">

                <p class="text-sm font-medium text-slate-500">
                    Active Coaches
                </p>

                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    ✓
                </div>

            </div>

            <h2 class="text-3xl font-bold text-emerald-600 mt-5">
                {{ $activeCoaches }}
            </h2>

            <p class="text-xs text-slate-400 mt-2">
                Currently active accounts
            </p>

        </div>


        {{-- Inactive Coaches --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex justify-between items-center">

                <p class="text-sm font-medium text-slate-500">
                    Inactive Coaches
                </p>

                <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-xl">
                    !
                </div>

            </div>

            <h2 class="text-3xl font-bold text-red-600 mt-5">
                {{ $inactiveCoaches }}
            </h2>

            <p class="text-xs text-slate-400 mt-2">
                Deactivated accounts
            </p>

        </div>

    </div>


    {{-- Coaches Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- Table Header --}}
        <div class="p-6 border-b border-slate-200">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                <div>
                    <h3 class="font-bold text-slate-900">
                        All Coaches
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        View and manage registered instructors.
                    </p>
                </div>


                {{-- Search and Filters --}}
                <div class="flex flex-col sm:flex-row gap-3">

                    <span wire:loading wire:target="search,statusFilter" class="self-center text-xs text-slate-400">
                        Updating results…
                    </span>

                    <div class="relative">

                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            aria-label="Search coaches by name, email, or phone"
                            placeholder="Search coaches..."
                            class="w-full sm:w-64 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        >

                    </div>


                    <select
                        wire:model.live="statusFilter"
                        aria-label="Filter coaches by status"
                        class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >

                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>

                    </select>


                </div>

            </div>

        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead class="bg-slate-50 border-b border-slate-200">

                    <tr>

                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">
                            Coach
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">
                            Contact Number
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">
                            Email Address
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">
                            Status
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">
                            Registration Date
                        </th>

                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase text-right">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($coaches as $coach)

                        <tr wire:key="coach-{{ $coach->id }}"
                            class="hover:bg-slate-50 transition">

                            {{-- Coach Name --}}
                            <td class="px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">

                                        {{ strtoupper(substr($coach->name, 0, 1)) }}

                                    </div>

                                    <div>

                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $coach->name }}
                                        </p>

                                        <p class="text-xs text-slate-400">
                                            ID: {{ $coach->id }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- Phone --}}
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $coach->phone ?: 'Not provided' }}
                            </td>


                            {{-- Email --}}
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $coach->email }}
                            </td>


                            {{-- Status --}}
                            <td class="px-6 py-4">

                                @if ($coach->is_active)

                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">

                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                        Active

                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold">

                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>

                                        Inactive

                                    </span>

                                @endif

                            </td>


                            {{-- Joined Date --}}
                            <td class="px-6 py-4 text-sm text-slate-500">

                                {{ $coach->created_at?->format('d M Y') }}

                            </td>


                            {{-- Actions --}}
                            <td class="px-6 py-4">

                                <div class="flex flex-wrap items-center justify-end gap-2 min-w-40">

                                    @if (auth()->user()->can('coaches.view'))
                                    <a href="{{ route('admin.coaches.view', $coach->id) }}"
                                        class="px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition">

                                        View

                                    </a>

                                    @endif
                                    @if (auth()->user()->can('coaches.manage'))
                                    <a href="{{ route('admin.coaches.edit', $coach->id) }}"
                                        class="px-3 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">

                                        Edit

                                    </a>
                                    <button type="button"
                                        wire:click="toggleActive({{ $coach->id }})"
                                        wire:confirm="{{ $coach->is_active ? 'Deactivate' : 'Activate' }} {{ $coach->name }}?"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleActive({{ $coach->id }})"
                                        class="px-3 py-2 text-xs font-medium rounded-lg transition disabled:opacity-50 {{ $coach->is_active ? 'text-red-700 bg-red-50 hover:bg-red-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                        <span wire:loading.remove wire:target="toggleActive({{ $coach->id }})">{{ $coach->is_active ? 'Deactivate' : 'Activate' }}</span>
                                        <span wire:loading wire:target="toggleActive({{ $coach->id }})">Saving…</span>
                                    </button>
                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="px-6 py-16 text-center">

                                <div class="text-4xl mb-4">
                                    ♙
                                </div>

                                <h3 class="font-semibold text-slate-800">
                                    No Coaches Found
                                </h3>

                                <p class="text-sm text-slate-500 mt-2">

                                    @if ($search || $statusFilter !== 'all')

                                        Try changing your search or filter criteria.

                                    @else

                                        Start by adding your first coach.

                                    @endif

                                </p>

                                @if (!$search && $statusFilter === 'all' && auth()->user()->can('coaches.manage'))

                                    <a href="{{ route('admin.coaches.create') }}"
                                        class="inline-block mt-5 text-sm font-semibold text-blue-600 hover:text-blue-700">

                                        + Add Your First Coach

                                    </a>

                                @endif

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        <div class="px-6 py-5 border-t border-slate-200">

            {{ $coaches->links() }}

        </div>

    </div>

</div>
