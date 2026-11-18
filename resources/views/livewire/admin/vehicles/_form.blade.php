<div class="space-y-8 p-6 sm:p-8">
    <section>
        <h2 class="mb-5 text-sm font-bold text-slate-800">Vehicle Information</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="registration-number" class="mb-2 block text-sm font-medium text-slate-700">Registration number <span class="text-red-500">*</span></label>
                <input id="registration-number" type="text" wire:model="registration_number" required maxlength="32" placeholder="e.g. ABC-1234" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('registration_number') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="chassis-number" class="mb-2 block text-sm font-medium text-slate-700">Chassis number <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="chassis-number" type="text" wire:model="chassis_number" maxlength="100" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('chassis_number') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="engine-number" class="mb-2 block text-sm font-medium text-slate-700">Engine number <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="engine-number" type="text" wire:model="engine_number" maxlength="100" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('engine_number') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vehicle-category" class="mb-2 block text-sm font-medium text-slate-700">Vehicle category <span class="font-normal text-slate-400">(optional)</span></label>
                <select id="vehicle-category" wire:model="vehicle_category_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">No category selected</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->category_code }} — {{ $category->category_name }}{{ $category->is_active ? '' : ' (Inactive)' }}</option>
                    @endforeach
                </select>
                @error('vehicle_category_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vehicle-brand" class="mb-2 block text-sm font-medium text-slate-700">Brand <span class="text-red-500">*</span></label>
                <input id="vehicle-brand" type="text" wire:model="brand" required maxlength="100" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('brand') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vehicle-model" class="mb-2 block text-sm font-medium text-slate-700">Model <span class="text-red-500">*</span></label>
                <input id="vehicle-model" type="text" wire:model="model" required maxlength="100" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('model') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="manufacturing-year" class="mb-2 block text-sm font-medium text-slate-700">Manufacturing year</label>
                <input id="manufacturing-year" type="number" wire:model="manufacturing_year" min="1950" max="{{ now()->year + 1 }}" step="1" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('manufacturing_year') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="fuel-type" class="mb-2 block text-sm font-medium text-slate-700">Fuel type <span class="text-red-500">*</span></label>
                <select id="fuel-type" wire:model="fuel_type" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach ($fuelTypes as $fuelType)<option value="{{ $fuelType }}">{{ ucfirst($fuelType) }}</option>@endforeach
                </select>
                @error('fuel_type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="transmission-type" class="mb-2 block text-sm font-medium text-slate-700">Transmission <span class="text-red-500">*</span></label>
                <select id="transmission-type" wire:model="transmission_type" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach ($transmissionTypes as $transmissionType)<option value="{{ $transmissionType }}">{{ ucfirst($transmissionType) }}</option>@endforeach
                </select>
                @error('transmission_type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="current-mileage" class="mb-2 block text-sm font-medium text-slate-700">Current mileage <span class="text-red-500">*</span></label>
                <input id="current-mileage" type="number" wire:model="current_mileage" min="0" step="0.1" inputmode="decimal" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('current_mileage') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="purchase-date" class="mb-2 block text-sm font-medium text-slate-700">Purchase date</label>
                <input id="purchase-date" type="date" wire:model="purchase_date" max="{{ today()->toDateString() }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('purchase_date') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vehicle-status" class="mb-2 block text-sm font-medium text-slate-700">Status <span class="text-red-500">*</span></label>
                <select id="vehicle-status" wire:model="status" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach ($statuses as $vehicleStatus)<option value="{{ $vehicleStatus }}">{{ ucfirst($vehicleStatus) }}</option>@endforeach
                </select>
                @error('status') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label for="vehicle-notes" class="mb-2 block text-sm font-medium text-slate-700">Additional notes</label>
                <textarea id="vehicle-notes" wire:model="notes" rows="3" maxlength="5000" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('notes') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="border-t border-slate-100 pt-7">
        <h2 class="mb-2 text-sm font-bold text-slate-800">Vehicle Documents</h2>
        <p class="mb-5 text-xs text-slate-500">PDF, JPG, JPEG, or PNG files up to 10 MB. Documents are stored privately and available to authorized Owners.</p>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                <label for="insurance-expiry" class="block text-sm font-medium text-slate-700">Insurance expiry date</label>
                <input id="insurance-expiry" type="date" wire:model="insurance_expiry_date" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('insurance_expiry_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @if ($isEditing && $vehicle->insurance_document_path)
                    <a class="inline-block text-xs font-semibold text-blue-700 hover:underline" href="{{ route('admin.vehicles.document', ['vehicle' => $vehicle, 'type' => 'insurance']) }}" target="_blank" rel="noopener">View current insurance document</a>
                @endif
                <label for="insurance-document" class="block text-sm font-medium text-slate-700">{{ $isEditing ? 'Replace insurance document' : 'Insurance document' }}</label>
                <input id="insurance-document" type="file" wire:model="insuranceDocument" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700">
                @error('insuranceDocument') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <p wire:loading wire:target="insuranceDocument" class="text-xs text-slate-500">Uploading insurance document…</p>
            </div>

            <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                <label for="revenue-expiry" class="block text-sm font-medium text-slate-700">Revenue license expiry date</label>
                <input id="revenue-expiry" type="date" wire:model="revenue_license_expiry_date" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('revenue_license_expiry_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @if ($isEditing && $vehicle->revenue_license_document_path)
                    <a class="inline-block text-xs font-semibold text-blue-700 hover:underline" href="{{ route('admin.vehicles.document', ['vehicle' => $vehicle, 'type' => 'revenue-license']) }}" target="_blank" rel="noopener">View current revenue license</a>
                @endif
                <label for="revenue-document" class="block text-sm font-medium text-slate-700">{{ $isEditing ? 'Replace revenue license' : 'Revenue license document' }}</label>
                <input id="revenue-document" type="file" wire:model="revenueLicenseDocument" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700">
                @error('revenueLicenseDocument') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <p wire:loading wire:target="revenueLicenseDocument" class="text-xs text-slate-500">Uploading revenue license…</p>
            </div>
        </div>
    </section>
</div>
