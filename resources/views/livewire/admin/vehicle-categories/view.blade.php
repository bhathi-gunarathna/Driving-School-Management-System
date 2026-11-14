<div class="space-y-6">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.vehicle-categories.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Category List</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Vehicle Category Details</h1>
            <p class="mt-1 text-sm text-slate-500">Category record #{{ $category->id }}</p>
        </div>
        @can('vehicle-categories.manage')
            <a href="{{ route('admin.vehicle-categories.edit', $category) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Edit Category</a>
        @endcan
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 border-b border-slate-200 p-6 sm:flex-row sm:items-center sm:p-8">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-lg font-bold text-blue-700">
                {{ $category->category_code }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Category Code</p>
                <h2 class="mt-1 break-words text-xl font-bold text-slate-900">{{ $category->category_name }}</h2>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                <span class="h-2 w-2 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                {{ $category->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Category Code</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $category->category_code }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Category Name</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $category->category_name }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Required Training Hours</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ number_format((float) $category->training_hours, 2) }} hours</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Course Fee</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ number_format((float) $category->price, 2) }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8 sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Description</dt>
                <dd class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $category->description ?: 'No description provided.' }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Created</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $category->created_at?->format('d M Y, h:i A') ?? '—' }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Last Updated</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $category->updated_at?->format('d M Y, h:i A') ?? '—' }}</dd>
            </div>
        </dl>
    </section>
</div>
