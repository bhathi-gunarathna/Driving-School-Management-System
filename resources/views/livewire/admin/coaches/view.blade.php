<div class="space-y-6">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.coaches.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Coaches</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Coach Profile</h1>
            <p class="mt-1 text-sm text-slate-500">Review coach account and registration details.</p>
        </div>

        @can('coaches.manage')
            <a href="{{ route('admin.coaches.edit', $coach->id) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                Edit Coach
            </a>
        @endcan
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 border-b border-slate-200 p-6 sm:flex-row sm:items-center sm:p-8">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-2xl font-bold text-blue-700">
                {{ strtoupper(substr($coach->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Coach account #{{ $coach->id }}</p>
                <h2 class="mt-1 break-words text-xl font-bold text-slate-900">{{ $coach->name }}</h2>
                <p class="mt-1 break-all text-sm text-slate-500">{{ $coach->email }}</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold {{ $coach->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                <span class="h-2 w-2 rounded-full {{ $coach->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                {{ $coach->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Full name</dt>
                <dd class="mt-2 break-words text-sm font-medium text-slate-900">{{ $coach->name }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email address</dt>
                <dd class="mt-2 break-all text-sm font-medium text-slate-900">{{ $coach->email }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contact number</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $coach->phone ?: 'Not provided' }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Account status</dt>
                <dd class="mt-2 text-sm font-medium {{ $coach->is_active ? 'text-emerald-700' : 'text-red-700' }}">{{ $coach->is_active ? 'Active' : 'Inactive' }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Assigned role</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $coach->getRoleNames()->implode(', ') ?: 'No role assigned' }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registration date</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $coach->created_at?->format('d M Y, h:i A') ?? '—' }}</dd>
            </div>
            <div class="bg-white p-6 sm:p-8">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Last updated</dt>
                <dd class="mt-2 text-sm font-medium text-slate-900">{{ $coach->updated_at?->format('d M Y, h:i A') ?? '—' }}</dd>
            </div>
        </dl>
    </section>
</div>
