<div class="space-y-6">
    <div>
        <a href="{{ route('admin.vehicles.view', $vehicle) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Vehicle Profile</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-900">Edit Vehicle</h1>
        <p class="mt-1 text-sm text-slate-500">Update {{ $vehicle->registration_number }} and maintain its document history.</p>
    </div>

    <form wire:submit="updateVehicle" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @include('livewire.admin.vehicles._form', ['isEditing' => true])
        <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:px-8">
            <a href="{{ route('admin.vehicles.view', $vehicle) }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-center text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
            <button type="submit" wire:confirm="Save these vehicle details and document changes?" wire:loading.attr="disabled" wire:target="updateVehicle,insuranceDocument,revenueLicenseDocument" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-wait disabled:opacity-50">
                <span wire:loading.remove wire:target="updateVehicle">Save Changes</span>
                <span wire:loading wire:target="updateVehicle">Saving…</span>
            </button>
        </div>
    </form>
</div>
