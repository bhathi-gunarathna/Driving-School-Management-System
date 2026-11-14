<div class="space-y-7">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Students</h1>
            <p class="mt-1 text-sm text-slate-500">Manage student profiles and course enrollments.</p>
        </div>
        @can('students.create')
            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"><span aria-hidden="true" class="text-lg">+</span> Register New Student</a>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm font-medium text-slate-500">Total Students</p><p class="mt-4 text-3xl font-bold text-slate-900">{{ $totalStudents }}</p><p class="mt-2 text-xs text-slate-400">All registered students</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm font-medium text-slate-500">Active Students</p><p class="mt-4 text-3xl font-bold text-emerald-600">{{ $activeStudents }}</p><p class="mt-2 text-xs text-slate-400">Currently active accounts</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm font-medium text-slate-500">Inactive Students</p><p class="mt-4 text-3xl font-bold text-red-600">{{ $inactiveStudents }}</p><p class="mt-2 text-xs text-slate-400">Preserved historical records</p></div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center lg:justify-between sm:p-6">
            <div><h2 class="font-bold text-slate-900">Student Register</h2><p class="mt-1 text-xs text-slate-500">Search student details or filter by status and enrolled category.</p></div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="sr-only" for="student-search">Search students</label>
                <input id="student-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search students..." class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <label class="sr-only" for="student-status">Filter by status</label>
                <select id="student-status" wire:model.live="statusFilter" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
                </select>
                <label class="sr-only" for="student-category">Filter by vehicle category</label>
                <select id="student-category" wire:model.live="vehicleCategoryFilter" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Vehicle Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->category_code }} — {{ $category->category_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div wire:loading wire:target="search,statusFilter,vehicleCategoryFilter" class="px-6 py-2 text-xs text-slate-400">Updating results…</div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Student</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">NIC Number</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Contact</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Vehicle Categories</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Registered</th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $student)
                        <tr wire:key="student-{{ $student->id }}" class="hover:bg-slate-50">
                            <td class="px-5 py-4"><p class="text-sm font-semibold text-slate-900">{{ $student->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $student->student_number }}</p></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $student->nic_number }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $student->contact_number }}</td>
                            <td class="px-5 py-4"><div class="flex max-w-64 flex-wrap gap-1.5">
                                @forelse ($student->vehicleCategories->where('pivot.status', 'active') as $category)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ $category->category_code }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">No active categories</span>
                                @endforelse
                            </div></td>
                            <td class="px-5 py-4 text-sm text-slate-500">{{ $student->created_at?->format('d M Y') }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $student->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ ucfirst($student->status) }}</span></td>
                            <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2">
                                @can('students.view_all')<a href="{{ route('admin.students.view', $student) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">View</a>@endcan
                                @can('students.update')
                                    <a href="{{ route('admin.students.edit', $student) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Edit</a>
                                    <button type="button" wire:click="toggleStatus({{ $student->id }})" wire:confirm="{{ $student->status === 'active' ? 'Deactivate' : 'Activate' }} {{ $student->full_name }}?" wire:loading.attr="disabled" wire:target="toggleStatus({{ $student->id }})" class="rounded-lg px-3 py-2 text-xs font-medium transition disabled:opacity-50 {{ $student->status === 'active' ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                        <span wire:loading.remove wire:target="toggleStatus({{ $student->id }})">{{ $student->status === 'active' ? 'Deactivate' : 'Activate' }}</span><span wire:loading wire:target="toggleStatus({{ $student->id }})">Saving…</span>
                                    </button>
                                @endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-16 text-center"><p class="font-semibold text-slate-800">No students found</p><p class="mt-2 text-sm text-slate-500">{{ $search || $statusFilter !== 'all' || $vehicleCategoryFilter ? 'Try changing the search or filters.' : 'Register a student to begin building the student register.' }}</p>
                            @if (!$search && $statusFilter === 'all' && !$vehicleCategoryFilter)
                                @can('students.create')<a href="{{ route('admin.students.create') }}" class="mt-5 inline-block text-sm font-semibold text-blue-600 hover:text-blue-700">+ Register the first student</a>@endcan
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-6 py-5">{{ $students->links() }}</div>
    </section>
</div>
