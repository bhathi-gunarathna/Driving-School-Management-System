<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-2xl font-bold text-slate-900">Dashboard Overview</h1><p class="mt-1 text-sm text-slate-500">Current school operations and recent activity.</p></div><span class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm">{{ now()->format(config('school.date_format', 'd M Y')) }}</span></div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([['Total Students',$totalStudents],['Active Students',$activeStudents],['Active Coaches',$activeCoaches],['Active Categories',$activeCategories],['Upcoming Trials',$upcomingTrialCount]] as [$label,$value])
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($value) }}</p></article>
        @endforeach
    </div>

    @if($vehicleAlertsVisible)
        <section class="space-y-5 rounded-2xl border border-amber-100 bg-amber-50/40 p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Vehicle Document Alerts</h2>
                    <p class="mt-1 text-sm text-slate-500">Current insurance and revenue license dates for active and maintenance vehicles.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.vehicles.expiry') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Manage Expiry</a>
                    <a href="{{ route('admin.vehicles.notifications') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Notification History</a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach([
                    ['Total Active Vehicles', $vehicleSummary['active_vehicles'], 'text-blue-700'],
                    ['Documents Expiring Soon', $vehicleSummary['expiring_soon'], 'text-amber-700'],
                    ['Expired Documents', $vehicleSummary['expired_documents'], 'text-red-700'],
                    ['Insurance Expiring', $vehicleSummary['insurance_expiring'], 'text-amber-700'],
                    ['Revenue Licenses Expiring', $vehicleSummary['licenses_expiring'], 'text-amber-700'],
                ] as [$label, $value, $color])
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                    </article>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <h3 class="font-semibold text-slate-900">Upcoming and Expired Documents</h3>
                    <span class="text-xs text-slate-500">Through {{ $vehicleThroughLabel }}</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($vehicleAlerts as $alert)
                        <a href="{{ route('admin.vehicles.view', $alert->vehicle_id) }}" class="flex flex-col gap-2 px-4 py-3 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $alert->registration_number }}</p>
                                <p class="text-xs text-slate-500">{{ $alert->document_type === 'revenue_license' ? 'Revenue License' : 'Insurance' }} · Expires {{ \Illuminate\Support\Carbon::parse($alert->expiry_date)->format(config('school.date_format', 'd M Y')) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($alert->days_remaining < 0)
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">{{ abs($alert->days_remaining) }} {{ abs($alert->days_remaining) === 1 ? 'day' : 'days' }} overdue</span>
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold uppercase text-red-700">Expired</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $alert->days_remaining === 0 ? 'Due today' : $alert->days_remaining.' days remaining' }}</span>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase text-amber-800">Expiring</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">No expired documents or documents expiring in the next 30 days.</p>
                    @endforelse
                </div>
                @if($vehicleAlerts->isNotEmpty())
                    <div class="border-t border-slate-100 px-4 py-3 text-right"><a href="{{ route('admin.vehicles.expiry') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">View all vehicle documents →</a></div>
                @endif
            </div>
        </section>
    @endif

    @if($financeVisible)
        <section class="space-y-5 rounded-2xl border border-blue-100 bg-blue-50/50 p-5 sm:p-6"><div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-bold text-slate-900">Financial Snapshot</h2><p class="mt-1 text-sm text-slate-500">Received values use actual receipts; balances use payment plans.</p></div><div class="flex flex-wrap gap-2"><a href="{{ route('admin.finance.overview') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Financial Overview</a><a href="{{ route('admin.finance.income') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold">Income Reports</a></div></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold uppercase text-slate-500">Total Payments Received</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ \App\Support\Lkr::format($receivedTotal) }}</p></article><article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold uppercase text-slate-500">Total Outstanding Balance</p><p class="mt-2 text-2xl font-bold text-amber-700">{{ \App\Support\Lkr::format($outstandingTotal) }}</p></article></div>
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2"><section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="font-semibold">Recent Payment Transactions</h3><div class="mt-3 divide-y divide-slate-100">@forelse($recentPayments as $payment)<div class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"><div><p class="font-medium">{{ $payment->student->full_name }}</p><p class="text-xs text-slate-500">{{ $payment->receipt_number }} · {{ $payment->payment_date->format(config('school.date_format', 'd M Y')) }}</p></div><strong>{{ \App\Support\Lkr::format($payment->amount) }}</strong></div>@empty<p class="py-5 text-sm text-slate-500">No payment transactions recorded.</p>@endforelse</div><a href="{{ route('admin.finance.income') }}" class="mt-2 inline-block text-sm font-semibold text-blue-700">View transactions and reports →</a></section><section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="font-semibold">7-Day Income Trend</h3><p class="mt-1 text-xs text-slate-500">Actual receipt dates</p><div class="mt-5 flex h-36 items-end gap-2 border-b border-slate-200">@foreach($dailyIncome as $day)<div class="flex h-full min-w-0 flex-1 flex-col justify-end text-center"><div title="{{ $day['date'] }} · {{ \App\Support\Lkr::format($day['income']) }}" class="mx-auto w-full max-w-8 rounded-t bg-blue-500" style="height: {{ max(3, ($day['income']/$maxDailyIncome)*88) }}%"></div><span class="mt-2 text-[10px] text-slate-500">{{ $day['label'] }}</span></div>@endforeach</div></section></div>
        </section>
    @endif

    @if($expensesVisible)
        <section class="space-y-5 rounded-2xl border border-emerald-100 bg-emerald-50/30 p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-bold text-slate-900">Expense Summary</h2><p class="mt-1 text-sm text-slate-500">Additional operating expenses, kept separate from student payment totals.</p></div><div class="flex flex-wrap gap-2"><a href="{{ route('admin.expenses.index') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Manage Expenses</a><a href="{{ route('admin.expenses.reports') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Expense Reports</a></div></div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    ["Today's Expenses", $expenseSummary['today']],
                    ['This Month\'s Expenses', $expenseSummary['month']],
                    ['This Year\'s Expenses', $expenseSummary['year']],
                    ['Total Expenses', $expenseSummary['total']],
                    ['Vehicle-related Expenses', $expenseSummary['vehicle']],
                    ['Petty Cash Expenses', $expenseSummary['petty_cash']],
                ] as [$label, $value])
                    <article class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-xl font-bold text-slate-900">{{ \App\Support\Lkr::format($value) }}</p></article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Recent Student Registrations</h2><div class="mt-3 divide-y divide-slate-100">@forelse($recentStudents as $student)<a href="{{ route('admin.students.view',$student) }}" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"><span><strong>{{ $student->full_name }}</strong><span class="ml-2 text-xs text-slate-500">{{ $student->student_number }}</span></span><span class="text-xs text-slate-500">{{ $student->created_at->format(config('school.date_format', 'd M Y')) }}</span></a>@empty<p class="py-5 text-sm text-slate-500">No student registrations yet.</p>@endforelse</div></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="font-bold">Upcoming Trials</h2><p class="mt-1 text-xs text-slate-500">Next scheduled examinations</p></div><a href="{{ route('admin.trials.index') }}" class="text-sm font-semibold text-blue-700">View trials</a></div><div class="mt-3 divide-y divide-slate-100">@forelse($upcomingTrials as $trial)<div class="flex items-center justify-between gap-3 py-3"><div><p class="text-sm font-semibold">{{ $trial->trial_number }}</p><p class="text-xs text-slate-500">{{ $trial->trial_date->format(config('school.date_format', 'd M Y')) }} · {{ $trial->location ?: 'Location not set' }}</p></div><span class="text-xs text-slate-500">{{ $trial->participants_count }} students</span></div>@empty<p class="py-5 text-sm text-slate-500">No upcoming trials.</p>@endforelse</div></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h2 class="font-bold">Quick Access</h2>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                @can('students.view_all')
                    <a href="{{ route('admin.students.index') }}" class="rounded-xl bg-slate-50 p-4 text-sm font-semibold hover:bg-blue-50">Manage Students</a>
                @endcan
                @can('coaches.view')
                    <a href="{{ route('admin.coaches.index') }}" class="rounded-xl bg-slate-50 p-4 text-sm font-semibold hover:bg-blue-50">Manage Coaches</a>
                @endcan
                @can('training.view')
                    <a href="{{ route('admin.training.index') }}" class="rounded-xl bg-slate-50 p-4 text-sm font-semibold hover:bg-blue-50">Training Management</a>
                @endcan
                @can('trials.view')
                    <a href="{{ route('admin.trials.index') }}" class="rounded-xl bg-slate-50 p-4 text-sm font-semibold hover:bg-blue-50">Trial Management</a>
                @endcan
                @can('vehicles.view')
                    <a href="{{ route('admin.vehicles.index') }}" class="group rounded-xl border border-slate-100 bg-slate-50 p-4 transition hover:border-blue-100 hover:bg-blue-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700 transition group-hover:bg-blue-200" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 11 1.3-4.2A2.5 2.5 0 0 1 8.7 5h6.6a2.5 2.5 0 0 1 2.4 1.8L19 11m-14 0h14a2 2 0 0 1 2 2v4h-2m-14 0H3v-4a2 2 0 0 1 2-2Zm2 6h10M7 17.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm13 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                            </svg>
                        </span>
                        <span class="mt-3 block text-sm font-semibold text-slate-900">Vehicle Management</span>
                        <span class="mt-1 block text-xs font-normal leading-5 text-slate-500">Manage school vehicles, vehicle details, insurance and revenue license documents.</span>
                    </a>
                @endcan
            </div>
        </section>
    </div>
</div>
