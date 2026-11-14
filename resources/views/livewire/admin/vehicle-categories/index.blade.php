<div class="space-y-7">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Vehicle Categories</h1>
            <p class="mt-1 text-sm text-slate-500">Manage course categories, training hours, and fees.</p>
        </div>
        @can('vehicle-categories.manage')
            <a href="{{ route('admin.vehicle-categories.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                <span aria-hidden="true" class="text-lg">+</span> Add New Category
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total Categories</p>
            <p class="mt-4 text-3xl font-bold text-slate-900">{{ $totalCategories }}</p>
            <p class="mt-2 text-xs text-slate-400">All configured categories</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Active Categories</p>
            <p class="mt-4 text-3xl font-bold text-emerald-600">{{ $activeCategories }}</p>
            <p class="mt-2 text-xs text-slate-400">Available for new registrations</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Inactive Categories</p>
            <p class="mt-4 text-3xl font-bold text-red-600">{{ $inactiveCategories }}</p>
            <p class="mt-2 text-xs text-slate-400">Kept for existing records</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <h2 class="font-bold text-slate-900">All Vehicle Categories</h2>
                <p class="mt-1 text-xs text-slate-500">Search by category code or name, then filter by status.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <label class="sr-only" for="category-search">Search categories</label>
                <input id="category-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search code or name..." class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-64">
                <label class="sr-only" for="category-status-filter">Filter by status</label>
                <select id="category-status-filter" wire:model.live="statusFilter" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div wire:loading wire:target="search,statusFilter" class="px-6 py-2 text-xs text-slate-400">Updating results…</div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Category</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Training Hours</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Course Fee</th>
                        <th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr wire:key="vehicle-category-{{ $category->id }}" class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-900">{{ $category->category_code }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $category->category_name }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ number_format((float) $category->training_hours, 2) }} hrs</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">{{ number_format((float) $category->price, 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @can('vehicle-categories.view')
                                        <a href="{{ route('admin.vehicle-categories.view', $category) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100">View</a>
                                    @endcan
                                    @can('vehicle-categories.manage')
                                        <a href="{{ route('admin.vehicle-categories.edit', $category) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-200">Edit</a>
                                        <button type="button" wire:click="toggleActive({{ $category->id }})" wire:confirm="{{ $category->is_active ? 'Deactivate' : 'Activate' }} {{ $category->category_code }} — {{ $category->category_name }}?" wire:loading.attr="disabled" wire:target="toggleActive({{ $category->id }})" class="rounded-lg px-3 py-2 text-xs font-medium transition disabled:opacity-50 {{ $category->is_active ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                            <span wire:loading.remove wire:target="toggleActive({{ $category->id }})">{{ $category->is_active ? 'Deactivate' : 'Activate' }}</span>
                                            <span wire:loading wire:target="toggleActive({{ $category->id }})">Saving…</span>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <p class="font-semibold text-slate-800">No vehicle categories found</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $search || $statusFilter !== 'all' ? 'Try changing the search or status filter.' : 'Add a category to make it available for future registrations.' }}</p>
                                @if (!$search && $statusFilter === 'all')
                                    @can('vehicle-categories.manage')
                                        <a href="{{ route('admin.vehicle-categories.create') }}" class="mt-5 inline-block text-sm font-semibold text-blue-600 hover:text-blue-700">+ Add the first category</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-5">{{ $categories->links() }}</div>
    </section>
</div>
