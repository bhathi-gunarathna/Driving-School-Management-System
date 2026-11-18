<div class="space-y-5">
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <label class="text-sm font-medium text-slate-700">Expense category <span class="text-red-600">*</span>
            <select wire:model="expense_category_id" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">Choose a category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (Inactive)' }}</option>@endforeach</select>
            @error('expense_category_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700">Vehicle <span class="font-normal text-slate-400">(optional)</span>
            <select wire:model="vehicle_id" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">No vehicle</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }} · {{ $vehicle->brand }} {{ $vehicle->model }}</option>@endforeach</select>
            @error('vehicle_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700 md:col-span-2">Expense title <span class="text-red-600">*</span>
            <input type="text" wire:model="title" maxlength="180" placeholder="e.g. Monthly fuel refill" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5">
            @error('title')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700">Amount (LKR) <span class="text-red-600">*</span>
            <input type="number" wire:model="amount" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5">
            @error('amount')<span class="mt-1 block text-xs text-red-600">Enter an amount greater than zero with up to two decimal places.</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700">Expense date <span class="text-red-600">*</span>
            <input type="date" wire:model="expense_date" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5">
            @error('expense_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700">Payment method <span class="text-red-600">*</span>
            <select wire:model="payment_method" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5">@foreach($paymentMethods as $method)<option value="{{ $method }}">{{ str($method)->replace('_', ' ')->title() }}</option>@endforeach</select>
            @error('payment_method')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700">Paid to <span class="font-normal text-slate-400">(optional)</span>
            <input type="text" wire:model="paid_to" maxlength="160" placeholder="Supplier or recipient" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5">
            @error('paid_to')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-medium text-slate-700 md:col-span-2">Description <span class="font-normal text-slate-400">(optional)</span>
            <textarea wire:model="description" rows="4" maxlength="5000" placeholder="Additional details" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
            @error('description')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700">Receipt or bill <span class="font-normal text-slate-400">(optional)</span>
                <input type="file" wire:model="receipt" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
            </label>
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or PDF up to 10 MB. Files are kept in private storage.</p>
            @error('receipt')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
            <div wire:loading wire:target="receipt" class="mt-2 text-xs font-medium text-blue-700">Uploading receipt…</div>
            @if(isset($expense) && $expense->receipt_path)
                <div class="mt-3 flex flex-wrap items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm">
                    <a href="{{ route('admin.expenses.receipt', $expense) }}" class="font-semibold text-blue-700">Download current receipt</a>
                    <label class="flex items-center gap-2 text-xs text-red-700"><input type="checkbox" wire:model="removeReceipt"> Remove current receipt when saving</label>
                </div>
            @endif
        </div>
    </div>
    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.expenses.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700">Cancel</a>
        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove>{{ $editing ? 'Save Expense' : 'Record Expense' }}</span><span wire:loading>Saving…</span></button>
    </div>
</div>
