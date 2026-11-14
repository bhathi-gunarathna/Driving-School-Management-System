<div class="space-y-8 p-6 sm:p-8">
    <section>
        <h2 class="mb-5 text-sm font-bold text-slate-800">Category Information</h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="category-code" class="mb-2 block text-sm font-medium text-slate-700">Category Code <span class="text-red-500">*</span></label>
                <input id="category-code" type="text" wire:model="category_code" required maxlength="20" placeholder="e.g. A1, B" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('category_code') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category-name" class="mb-2 block text-sm font-medium text-slate-700">Category Name <span class="text-red-500">*</span></label>
                <input id="category-name" type="text" wire:model="category_name" required maxlength="150" placeholder="e.g. Motor Cars" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('category_name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="training-hours" class="mb-2 block text-sm font-medium text-slate-700">Required Training Hours <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input id="training-hours" type="number" wire:model="training_hours" required min="0.01" step="0.01" inputmode="decimal" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-16 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="pointer-events-none absolute right-4 top-3 text-sm text-slate-400">hours</span>
                </div>
                @error('training_hours') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category-price" class="mb-2 block text-sm font-medium text-slate-700">Course Fee <span class="text-red-500">*</span></label>
                <input id="category-price" type="number" wire:model="price" required min="0" step="0.01" inputmode="decimal" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('price') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="category-description" class="mb-2 block text-sm font-medium text-slate-700">Description <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea id="category-description" wire:model="description" rows="4" maxlength="2000" placeholder="Add notes about this category or course..." class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                @error('description') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="border-t border-slate-100 pt-7">
        <h2 class="mb-5 text-sm font-bold text-slate-800">Availability</h2>
        <label for="category-active" class="flex cursor-pointer items-start gap-3">
            <input id="category-active" type="checkbox" wire:model="is_active" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            <span>
                <span class="block text-sm font-medium text-slate-800">Active category</span>
                <span class="mt-1 block text-xs text-slate-500">Active categories can be offered for new registrations. Existing records remain unchanged when a category is deactivated.</span>
            </span>
        </label>
        @error('is_active') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>
</div>
