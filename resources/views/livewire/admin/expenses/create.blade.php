<div class="mx-auto max-w-4xl space-y-5">
    <div><a href="{{ route('admin.expenses.index') }}" class="text-sm font-semibold text-blue-700">← Back to Expenses</a><h1 class="mt-2 text-2xl font-bold text-slate-900">Record Additional Expense</h1><p class="mt-1 text-sm text-slate-500">Record an operational expense separately from student payments.</p></div>
    <form wire:submit="save" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><h2 class="mb-5 font-semibold text-slate-900">Expense Details</h2>@include('livewire.admin.expenses._form', ['editing' => false])</form>
</div>
