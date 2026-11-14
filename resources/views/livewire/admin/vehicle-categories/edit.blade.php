<div class="space-y-6">
    <div>
        <a href="{{ route('admin.vehicle-categories.view', $category) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Category Profile</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-900">Edit Vehicle Category</h1>
        <p class="mt-1 text-sm text-slate-500">Update {{ $category->category_code }} — {{ $category->category_name }}.</p>
    </div>

    <form wire:submit="updateCategory" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @include('livewire.admin.vehicle-categories._form')
        <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:px-8">
            <a href="{{ route('admin.vehicle-categories.view', $category) }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="updateCategory" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="updateCategory">Save Changes</span>
                <span wire:loading wire:target="updateCategory">Saving…</span>
            </button>
        </div>
    </form>
</div>
