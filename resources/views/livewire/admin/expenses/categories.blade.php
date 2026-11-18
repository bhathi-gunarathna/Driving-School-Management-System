<div class="mx-auto max-w-5xl space-y-6">
    <div><a href="{{ route('admin.expenses.index') }}" class="text-sm font-semibold text-blue-700">← Back to Expenses</a><h1 class="mt-2 text-2xl font-bold text-slate-900">Expense Categories</h1><p class="mt-1 text-sm text-slate-500">Maintain categories used to organize operating expenses.</p></div>
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @error('categoryDelete')<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-slate-900">{{ $editingId ? 'Edit Category' : 'Add Category' }}</h2>
        <form wire:submit="saveCategory" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <label class="text-sm font-medium text-slate-700">Category name<input type="text" wire:model="name" maxlength="120" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5">@error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
            <label class="flex items-center gap-2 py-3 text-sm text-slate-700"><input type="checkbox" wire:model="is_active"> Active</label>
            <div class="flex gap-2"><button wire:loading.attr="disabled" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">{{ $editingId ? 'Save Changes' : 'Add Category' }}</button>@if($editingId)<button type="button" wire:click="cancelEdit" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Cancel</button>@endif</div>
        </form>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Category</th><th class="px-4 py-3">Expenses recorded</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($categories as $category)<tr><td class="px-4 py-3 font-semibold text-slate-900">{{ $category->name }}</td><td class="px-4 py-3 text-slate-600">{{ number_format($category->expenses_count) }}</td><td class="px-4 py-3">@if($category->is_active)<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>@else<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactive</span>@endif</td><td class="px-4 py-3"><div class="flex gap-3"><button type="button" wire:click="editCategory({{ $category->id }})" class="font-semibold text-blue-700">Edit</button>@if($category->expenses_count === 0)<button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Delete this unused category?" class="font-semibold text-red-700">Delete</button>@else<span class="text-xs text-slate-400" title="Categories referenced by expenses cannot be deleted">In use</span>@endif</div></td></tr>
            @empty<tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No expense categories have been created.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
