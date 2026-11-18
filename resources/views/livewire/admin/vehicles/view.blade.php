<div class="space-y-6">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @php
        $insuranceDays = $vehicle->insurance_expiry_date ? (int) today()->diffInDays($vehicle->insurance_expiry_date, false) : null;
        $licenseDays = $vehicle->revenue_license_expiry_date ? (int) today()->diffInDays($vehicle->revenue_license_expiry_date, false) : null;
        $expiryBadge = fn ($days) => $days === null ? 'bg-slate-100 text-slate-600' : ($days < 0 ? 'bg-red-100 text-red-700' : ($days <= 30 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'));
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.vehicles.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Vehicles</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Vehicle Profile</h1>
            <p class="mt-1 text-sm text-slate-500">Vehicle record #{{ $vehicle->id }}</p>
        </div>
        @can('vehicles.manage')
            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">Edit Vehicle</a>
        @endcan
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registration number</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $vehicle->registration_number }}</h2>
                <p class="mt-1 text-slate-500">{{ $vehicle->brand }} {{ $vehicle->model }}{{ $vehicle->manufacturing_year ? ' · '.$vehicle->manufacturing_year : '' }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1.5 text-sm font-semibold {{ $vehicle->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($vehicle->status === 'maintenance' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ ucfirst($vehicle->status) }}</span>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 sm:p-8 xl:grid-cols-3">
            @foreach ([
                'Vehicle category' => $vehicle->vehicleCategory ? $vehicle->vehicleCategory->category_code.' — '.$vehicle->vehicleCategory->category_name : 'Not assigned',
                'Chassis number' => $vehicle->chassis_number ?: 'Not recorded',
                'Engine number' => $vehicle->engine_number ?: 'Not recorded',
                'Brand' => $vehicle->brand,
                'Model' => $vehicle->model,
                'Manufacturing year' => $vehicle->manufacturing_year ?: 'Not recorded',
                'Fuel type' => ucfirst($vehicle->fuel_type),
                'Transmission' => ucfirst($vehicle->transmission_type),
                'Current mileage' => number_format((float) $vehicle->current_mileage, 1),
                'Purchase date' => $vehicle->purchase_date?->format('d M Y') ?? 'Not recorded',
                'Registered' => $vehicle->created_at?->format('d M Y, H:i') ?? '—',
                'Last updated' => $vehicle->updated_at?->format('d M Y, H:i') ?? '—',
            ] as $label => $value)
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-2 break-words text-sm font-medium text-slate-800">{{ $value }}</p></div>
            @endforeach
            @if ($vehicle->notes)
                <div class="sm:col-span-2 xl:col-span-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Additional notes</p><p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $vehicle->notes }}</p></div>
            @endif
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-5"><h2 class="text-lg font-bold text-slate-900">Document Expiry</h2><p class="mt-1 text-sm text-slate-500">Renewal reminders are sent to Owners before expiry and when documents expire.</p></div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <article class="rounded-xl border border-slate-200 p-5">
                <p class="text-sm font-semibold text-slate-800">Insurance</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ $vehicle->insurance_expiry_date?->format('d M Y') ?? 'Expiry date not recorded' }}</p>
                @if ($insuranceDays !== null)<span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $expiryBadge($insuranceDays) }}">{{ $insuranceDays < 0 ? 'Expired '.abs($insuranceDays).' days ago' : ($insuranceDays === 0 ? 'Expires today' : 'Expires in '.$insuranceDays.' days') }}</span>@endif
                <div class="mt-3">@if ($vehicle->insurance_document_path)<a href="{{ route('admin.vehicles.document', ['vehicle' => $vehicle, 'type' => 'insurance']) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-blue-700 hover:underline">Open insurance document</a>@else<p class="text-sm text-slate-500">No document uploaded</p>@endif</div>
            </article>
            <article class="rounded-xl border border-slate-200 p-5">
                <p class="text-sm font-semibold text-slate-800">Revenue license</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ $vehicle->revenue_license_expiry_date?->format('d M Y') ?? 'Expiry date not recorded' }}</p>
                @if ($licenseDays !== null)<span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $expiryBadge($licenseDays) }}">{{ $licenseDays < 0 ? 'Expired '.abs($licenseDays).' days ago' : ($licenseDays === 0 ? 'Expires today' : 'Expires in '.$licenseDays.' days') }}</span>@endif
                <div class="mt-3">@if ($vehicle->revenue_license_document_path)<a href="{{ route('admin.vehicles.document', ['vehicle' => $vehicle, 'type' => 'revenue-license']) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-blue-700 hover:underline">Open revenue license</a>@else<p class="text-sm text-slate-500">No document uploaded</p>@endif</div>
            </article>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6 sm:p-8"><h2 class="text-lg font-bold text-slate-900">Document Renewal History</h2><p class="mt-1 text-sm text-slate-500">Previous document files remain available here when replaced.</p></div>
        <div class="divide-y divide-slate-100">
            @forelse ($vehicle->documentRenewals as $renewal)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $renewal->document_type === 'insurance' ? 'Insurance' : 'Revenue license' }} {{ str_replace('_', ' ', ucfirst($renewal->event_type)) }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $renewal->previous_expiry_date?->format('d M Y') ?? 'No previous expiry' }} <span aria-hidden="true">→</span> {{ $renewal->expiry_date?->format('d M Y') ?? 'Expiry removed' }}</p>
                        <p class="mt-1 text-xs text-slate-400">Recorded {{ $renewal->recorded_on?->format('d M Y') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm font-semibold">
                        @if ($renewal->previous_document_path)<a href="{{ route('admin.vehicles.renewal-document', $renewal) }}?version=previous" target="_blank" rel="noopener" class="text-blue-700 hover:underline">Previous file</a>@endif
                        @if ($renewal->document_path)<a href="{{ route('admin.vehicles.renewal-document', $renewal) }}" target="_blank" rel="noopener" class="text-blue-700 hover:underline">Recorded file</a>@endif
                    </div>
                </article>
            @empty
                <p class="px-6 py-12 text-center text-sm text-slate-500">No document renewal history recorded.</p>
            @endforelse
        </div>
    </section>
</div>
