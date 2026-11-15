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

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6"><h2 class="text-lg font-bold text-slate-900">Training</h2><p class="mt-1 text-sm text-slate-500">Coach assignments and immutable training history by category enrollment.</p></div>
        @forelse($student->enrollments as $enrollment)
            @php($metric = $trainingProgress[$enrollment->id])
            <div wire:key="student-training-{{ $enrollment->id }}" class="border-b border-slate-100 p-5 last:border-b-0 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h3 class="font-semibold text-slate-900">{{ $enrollment->vehicleCategory->category_code }} ? {{ $enrollment->vehicleCategory->category_name }}</h3><p class="mt-1 text-xs text-slate-500">Enrollment {{ ucfirst($enrollment->status) }} ? Required {{ number_format($metric['required_hours'], 2) }} hrs</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($metric['status'])->replace('_',' ')->title() }}</span></div>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3"><div><p class="text-xs uppercase text-slate-500">Completed</p><p class="mt-1 text-sm font-semibold">{{ number_format($metric['completed_hours'], 2) }} hrs <span class="font-normal text-slate-500">({{ $metric['completed_minutes'] }} min)</span></p></div><div><p class="text-xs uppercase text-slate-500">Remaining</p><p class="mt-1 text-sm font-semibold">{{ number_format($metric['remaining_hours'], 2) }} hrs</p></div><div><p class="text-xs uppercase text-slate-500">Progress</p><div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" style="width: {{ min(100, $metric['percentage']) }}%"></div></div><p class="mt-1 text-xs text-slate-500">{{ $metric['percentage'] }}%</p></div></div>
                <div class="mt-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Currently assigned coaches</p><div class="mt-2 flex flex-wrap gap-2">@forelse($enrollment->coachAssignments->where('status','active') as $assignment)<span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">{{ $assignment->coach->name }}</span>@empty<span class="text-sm text-slate-400">No active coach assigned</span>@endforelse</div>@if($enrollment->coachAssignments->where('status','inactive')->isNotEmpty())<details class="mt-2 text-xs text-slate-500"><summary class="cursor-pointer">Previous coach assignments</summary><div class="mt-1 space-y-1">@foreach($enrollment->coachAssignments->where('status','inactive') as $oldAssignment)<p>{{ $oldAssignment->coach->name }} ? deactivated {{ $oldAssignment->deactivated_at?->format('d M Y') ?? '?' }}</p>@endforeach</div></details>@endif</div>
                <div class="mt-5 overflow-x-auto"><p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Session history</p><table class="w-full min-w-[520px] text-left"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-xs text-slate-500">Date</th><th class="px-3 py-2 text-xs text-slate-500">Coach</th><th class="px-3 py-2 text-xs text-slate-500">Duration</th><th class="px-3 py-2 text-xs text-slate-500">Notes</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($enrollment->trainingSessions as $session)<tr><td class="px-3 py-3 text-sm">{{ $session->training_date->format('d M Y') }}</td><td class="px-3 py-3 text-sm">{{ $session->coach->name }}</td><td class="px-3 py-3 text-sm">{{ intdiv($session->duration_minutes,60) }}h {{ $session->duration_minutes % 60 }}m</td><td class="px-3 py-3 text-sm">{{ $session->notes ?: '?' }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-4 text-sm text-slate-500">No training sessions recorded.</td></tr>@endforelse</tbody></table></div>
            </div>
        @empty
            <p class="p-6 text-sm text-slate-500">No category enrollments available for training.</p>
        @endforelse
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6"><h2 class="text-lg font-bold text-slate-900">Trial History</h2><p class="mt-1 text-sm text-slate-500">Every category attempt remains in the record, including re-trials and result corrections.</p></div>
        @forelse($student->enrollments as $enrollment)
            @php($history = $trialHistory[$enrollment->id])
            <div wire:key="student-trials-{{ $enrollment->id }}" class="border-b border-slate-100 p-5 last:border-0 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-semibold text-slate-900">{{ $enrollment->vehicleCategory->category_code }} ? {{ $enrollment->vehicleCategory->category_name }}</h3><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">{{ $history['status'] }}</span></div>
                <div class="mt-3 overflow-x-auto"><table class="w-full min-w-[600px] text-left"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-xs font-semibold uppercase text-slate-500">Trial Date</th><th class="px-3 py-2 text-xs font-semibold uppercase text-slate-500">Trial Number</th><th class="px-3 py-2 text-xs font-semibold uppercase text-slate-500">Result</th><th class="px-3 py-2 text-xs font-semibold uppercase text-slate-500">Recorded By</th><th class="px-3 py-2 text-xs font-semibold uppercase text-slate-500">Recorded At</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse($history['attempts'] as $attempt)
                        <tr><td class="px-3 py-3 text-sm">{{ $attempt->participant->trial->trial_date->format('d M Y') }}</td><td class="px-3 py-3 text-sm font-medium">{{ $attempt->participant->trial->trial_number }}</td><td class="px-3 py-3 text-sm">{{ str($attempt->result)->title() }}</td><td class="px-3 py-3 text-sm">{{ $attempt->recordedBy?->name ?? '?' }}</td><td class="px-3 py-3 text-sm">{{ $attempt->recorded_at?->format('d M Y H:i') ?? '?' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-4 text-sm text-slate-500">Not Yet Attempted</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        @empty
            <p class="p-6 text-sm text-slate-500">No vehicle category enrollments to show.</p>
        @endforelse
    </section>

    @can('payments.view')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-6 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-bold text-slate-900">Course Payments</h2><p class="mt-1 text-sm text-slate-500">Installments are planned amounts; received totals come only from recorded payment receipts.</p></div>@if($student->paymentPlan && (float) $student->paymentPlan->outstanding_balance > 0 && auth()->user()->can('payments.manage'))<a href="{{ route('admin.payments.create', $student) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-blue-700">Record Payment</a>@endif</div>
        @if(!$student->paymentPlan)
            <div class="p-6"><p class="text-sm text-slate-500">No payment plan exists for this student.</p>@can('payments.manage')<form wire:submit="createPaymentPlan" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"><div class="min-w-0 flex-1"><label class="mb-2 block text-sm font-medium text-slate-700">Set up a plan from saved enrollment fees</label><select wire:model="newPaymentPlanType" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="full_payment">Full payment</option><option value="two_installments">2 installments</option><option value="three_installments">3 installments</option></select>@error('newPaymentPlanType')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror @error('paymentPlan')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror</div><button wire:loading.attr="disabled" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove>Create Payment Plan</span><span wire:loading>Creating…</span></button></form>@endcan</div>
        @else
            <div class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-4">
                <div class="bg-white p-5"><p class="text-xs uppercase text-slate-500">Agreed fee</p><p class="mt-1 text-lg font-bold">{{ number_format((float) $student->paymentPlan->total_agreed_fee, 2) }}</p></div>
                <div class="bg-white p-5"><p class="text-xs uppercase text-slate-500">Received</p><p class="mt-1 text-lg font-bold text-emerald-700">{{ number_format((float) $student->paymentPlan->total_amount_paid, 2) }}</p></div>
                <div class="bg-white p-5"><p class="text-xs uppercase text-slate-500">Outstanding</p><p class="mt-1 text-lg font-bold text-amber-700">{{ number_format((float) $student->paymentPlan->outstanding_balance, 2) }}</p></div>
                <div class="bg-white p-5"><p class="text-xs uppercase text-slate-500">Payment status</p><p class="mt-1 font-bold text-slate-900">{{ str($student->paymentPlan->payment_status)->replace('_', ' ')->title() }}</p><p class="mt-1 text-xs text-slate-500">{{ str($student->paymentPlan->plan_type)->replace('_', ' ')->title() }}</p></div>
            </div>
            <div class="border-t border-slate-100 p-5"><h3 class="text-sm font-semibold text-slate-800">Planned installment schedule</h3><div class="mt-3 flex flex-wrap gap-2">@foreach($student->paymentPlan->installments as $installment)<span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-600">{{ $installment->installment_number }}: {{ number_format((float) $installment->planned_amount, 2) }}</span>@endforeach</div></div>
        @endif
        @can('payments.view')<div class="border-t border-slate-200"><h3 class="px-6 pt-5 text-sm font-semibold text-slate-800">Payment history</h3><div class="overflow-x-auto"><table class="w-full min-w-[600px] text-left"><thead class="bg-slate-50"><tr><th class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Date</th><th class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Receipt</th><th class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Method</th><th class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Amount</th><th class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Received by</th><th></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($student->payments as $payment)<tr wire:key="student-payment-{{ $payment->id }}"><td class="px-6 py-4 text-sm">{{ $payment->payment_date->format('d M Y') }}</td><td class="px-6 py-4 text-sm font-medium">{{ $payment->receipt_number }}</td><td class="px-6 py-4 text-sm">{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td><td class="px-6 py-4 text-sm font-semibold">{{ number_format((float) $payment->amount, 2) }}</td><td class="px-6 py-4 text-sm">{{ $payment->receivedBy?->name ?? '—' }}</td><td class="px-6 py-4"><a href="{{ route('admin.payments.receipt', $payment) }}" class="text-sm text-blue-700 hover:underline">Receipt</a></td></tr>@empty<tr><td colspan="6" class="px-6 py-6 text-sm text-slate-500">No payments have been received.</td></tr>@endforelse</tbody></table></div></div>@endcan
    </section>
    @endcan

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
