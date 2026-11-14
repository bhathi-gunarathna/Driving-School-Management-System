<div class="space-y-6">
    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.students.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Student List</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Student Profile</h1>
            <p class="mt-1 text-sm text-slate-500">Student number {{ $student->student_number }}</p>
        </div>
        @can('students.update')
            <a href="{{ route('admin.students.edit', $student) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Edit Student</a>
        @endcan
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 border-b border-slate-200 p-6 sm:flex-row sm:items-center sm:p-8">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-2xl font-bold text-blue-700">{{ strtoupper(substr($student->full_name, 0, 1)) }}</div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $student->student_number }}</p>
                <h2 class="mt-1 break-words text-xl font-bold text-slate-900">{{ $student->full_name }}</h2>
                <p class="mt-1 text-sm text-slate-500">NIC: {{ $student->nic_number }}</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold {{ $student->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}"><span class="h-2 w-2 rounded-full {{ $student->status === 'active' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>{{ ucfirst($student->status) }}</span>
        </div>

        <dl class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
            <div class="bg-white p-6 sm:p-8"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Full Name</dt><dd class="mt-2 text-sm font-medium text-slate-900">{{ $student->full_name }}</dd></div>
            <div class="bg-white p-6 sm:p-8"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Student Number</dt><dd class="mt-2 text-sm font-medium text-slate-900">{{ $student->student_number }}</dd></div>
            <div class="bg-white p-6 sm:p-8"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">NIC Number</dt><dd class="mt-2 text-sm font-medium text-slate-900">{{ $student->nic_number }}</dd></div>
            <div class="bg-white p-6 sm:p-8"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date of Birth</dt><dd class="mt-2 text-sm font-medium text-slate-900">{{ $student->date_of_birth->format('d M Y') }}</dd></div>
            <div class="bg-white p-6 sm:p-8"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contact Number</dt><dd class="mt-2 text-sm font-medium text-slate-900">{{ $student->contact_number }}</dd></div>
            <div class="bg-white p-6 sm:p-8"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Registered</dt><dd class="mt-2 text-sm font-medium text-slate-900">{{ $student->created_at?->format('d M Y, h:i A') }}</dd></div>
            <div class="bg-white p-6 sm:p-8 sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Address</dt><dd class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $student->address }}</dd></div>
        </dl>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6"><h2 class="text-lg font-bold text-slate-900">Vehicle Category Enrollments</h2><p class="mt-1 text-sm text-slate-500">Agreed fees and training hours are preserved from each enrollment date.</p></div>
        @if ($student->vehicleCategories->isEmpty())
            <p class="p-6 text-sm text-slate-500">No vehicle categories have been assigned.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-left">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Category</th><th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Agreed Fee</th><th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Required Hours</th><th class="px-6 py-4 text-xs font-semibold uppercase text-slate-500">Enrollment Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($student->vehicleCategories as $category)
                            <tr wire:key="student-category-{{ $category->pivot->id }}">
                                <td class="px-6 py-4"><p class="text-sm font-semibold text-slate-900">{{ $category->category_code }} — {{ $category->category_name }}</p><p class="mt-1 text-xs text-slate-400">Category currently {{ $category->is_active ? 'active' : 'inactive' }}</p></td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ number_format((float) $category->pivot->agreed_fee, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ number_format((float) $category->pivot->required_training_hours, 2) }} hrs</td>
                                <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $category->pivot->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($category->pivot->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50"><tr><th class="px-6 py-4 text-sm font-bold text-slate-900">Current agreed total (active enrollments)</th><td class="px-6 py-4 text-sm font-bold text-slate-900">{{ $totalAgreedFee }}</td><td class="px-6 py-4 text-sm font-bold text-slate-900">{{ $totalTrainingHours }} hrs</td><td></td></tr></tfoot>
                </table>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Medical Report</h2>
        @if ($student->medical_report_path)
            <a href="{{ route('admin.students.medical-report', $student) }}" target="_blank" rel="noopener" class="mt-4 inline-block">
                <img src="{{ route('admin.students.medical-report', $student) }}" alt="Medical report for {{ $student->full_name }}" loading="lazy" class="max-h-96 max-w-full rounded-xl border border-slate-200 object-contain">
                <span class="mt-2 block text-sm font-medium text-blue-600">Open medical report</span>
            </a>
        @else
            <p class="mt-2 text-sm text-slate-500">No medical report uploaded.</p>
        @endif
    </section>
</div>
