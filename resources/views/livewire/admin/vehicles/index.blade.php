<div class="space-y-7">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Vehicle Management</h1>
            <p class="mt-1 text-sm text-slate-500">Manage fleet records, categories, and document expiry dates.</p>
        </div>
        @can('vehicles.manage')
            <a href="{{ route('admin.vehicles.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"><span aria-hidden="true" class="text-lg">+</span> Add Vehicle</a>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Total Vehicles', $totalVehicles, 'text-slate-900'], ['Active', $activeVehicles, 'text-emerald-600'], ['In Maintenance', $maintenanceVehicles, 'text-amber-600'], ['Inactive', $inactiveVehicles, 'text-slate-500']] as [$label, $count, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-bold {{ $color }}">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 sm:p-6">
            <div>
                <h2 class="font-bold text-slate-900">Vehicles</h2>
                <p class="mt-1 text-xs text-slate-500">Search registration, chassis, engine, brand or model. Narrow results by status and category.</p>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <label class="sr-only" for="vehicle-search">Search vehicles</label>
                <input id="vehicle-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search vehicles…" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <label class="sr-only" for="vehicle-status-filter">Filter by status</label>
                <select id="vehicle-status-filter" wire:model.live="statusFilter" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All statuses</option>
                    @foreach ($statuses as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach
                </select>
                <label class="sr-only" for="vehicle-category-filter">Filter by category</label>
                <select id="vehicle-category-filter" wire:model.live="categoryFilter" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All categories</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->category_code }} — {{ $category->category_name }}</option>@endforeach
                </select>
            </div>
        </div>

        <div wire:loading wire:target="search,statusFilter,categoryFilter" class="px-6 py-2 text-xs text-slate-400">Updating results…</div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-left">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Vehicle</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Category</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Mileage</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Insurance</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Revenue License</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($vehicles as $vehicle)
                        <tr wire:key="vehicle-{{ $vehicle->id }}" class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-slate-900">{{ $vehicle->registration_number }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $vehicle->brand }} {{ $vehicle->model }}{{ $vehicle->manufacturing_year ? ' · '.$vehicle->manufacturing_year : '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $vehicle->vehicleCategory ? $vehicle->vehicleCategory->category_code.' — '.$vehicle->vehicleCategory->category_name : '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $vehicle->current_mileage, 1) }}</td>
                            <td class="px-5 py-4 text-sm {{ $vehicle->insurance_expiry_date && $vehicle->insurance_expiry_date->lt(today()) ? 'text-red-600' : 'text-slate-600' }}">{{ $vehicle->insurance_expiry_date?->format('d M Y') ?? 'Not recorded' }}</td>
                            <td class="px-5 py-4 text-sm {{ $vehicle->revenue_license_expiry_date && $vehicle->revenue_license_expiry_date->lt(today()) ? 'text-red-600' : 'text-slate-600' }}">{{ $vehicle->revenue_license_expiry_date?->format('d M Y') ?? 'Not recorded' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $vehicle->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($vehicle->status === 'maintenance' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($vehicle->status) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('admin.vehicles.view', $vehicle) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">View</a>
                                    @can('vehicles.manage')
                                        <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Edit</a>
                                        @php($nextStatus = $vehicle->status === 'inactive' ? 'active' : 'inactive')
                                        <button type="button" wire:click="setStatus({{ $vehicle->id }}, '{{ $nextStatus }}')" wire:confirm="Set {{ $vehicle->registration_number }} to {{ $nextStatus }}?" wire:loading.attr="disabled" wire:target="setStatus({{ $vehicle->id }}, '{{ $nextStatus }}')" class="rounded-lg px-3 py-2 text-xs font-medium {{ $nextStatus === 'active' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-red-50 text-red-700 hover:bg-red-100' }} disabled:opacity-50">
                                            <span wire:loading.remove wire:target="setStatus({{ $vehicle->id }}, '{{ $nextStatus }}')">Set {{ ucfirst($nextStatus) }}</span>
                                            <span wire:loading wire:target="setStatus({{ $vehicle->id }}, '{{ $nextStatus }}')">Saving…</span>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-16 text-center"><p class="font-semibold text-slate-800">No vehicles found</p><p class="mt-2 text-sm text-slate-500">Try changing the search or filters, or register a vehicle.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-6 py-5">{{ $vehicles->links() }}</div>
    </section>
</div>
