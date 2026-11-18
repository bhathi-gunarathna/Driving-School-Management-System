<div class="space-y-6">
    <div>
        <a href="{{ route('admin.students.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">&larr; Back to Students</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-900">Register Student</h1>
        <p class="mt-1 text-sm text-slate-500">Add student details and select one or more active vehicle categories.</p>
    </div>

    <form wire:submit="registerStudent" class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900">Personal Information</h2>
            </div>
            <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                <div>
                    <label for="student-full-name" class="mb-2 block text-sm font-medium text-slate-700">Full name <span class="text-red-500">*</span></label>
                    <input id="student-full-name" type="text" wire:model="full_name" autocomplete="name" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('full_name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="student-nic" class="mb-2 block text-sm font-medium text-slate-700">NIC number <span class="text-red-500">*</span></label>
                    <input id="student-nic" type="text" wire:model="nic_number" autocomplete="off" maxlength="30" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nic_number') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="student-dob" class="mb-2 block text-sm font-medium text-slate-700">Date of birth <span class="text-red-500">*</span></label>
                    <input id="student-dob" type="date" wire:model="date_of_birth" max="{{ now()->subDay()->toDateString() }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('date_of_birth') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="student-contact" class="mb-2 block text-sm font-medium text-slate-700">Contact number <span class="text-red-500">*</span></label>
                    <input id="student-contact" type="tel" wire:model="contact_number" autocomplete="tel" maxlength="25" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('contact_number') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label for="student-address" class="mb-2 block text-sm font-medium text-slate-700">Address <span class="text-red-500">*</span></label>
                    <textarea id="student-address" wire:model="address" rows="3" maxlength="5000" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('address') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    @include('livewire.admin.students._private-image-upload', [
                        'field' => 'profilePhoto', 'title' => 'Student Profile Photo',
                        'description' => 'Add a student portrait. On supported mobile devices, the file picker can open the camera.',
                        'temporaryUpload' => $profilePhoto,
                    ])
                </div>
                <div class="md:col-span-2">
                    @include('livewire.admin.students._private-image-upload', [
                        'field' => 'medicalReport', 'title' => 'Medical Report',
                        'description' => 'Upload a clear image of the student’s medical report. This file is stored privately and only shown to authorized Owners.',
                        'temporaryUpload' => $medicalReport,
                    ])
                    <div class="mt-4">
                        <label for="medical-report-reference" class="mb-2 block text-sm font-medium text-slate-700">Medical report reference <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="medical-report-reference" type="text" wire:model="medicalReportReference" maxlength="100" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter the report reference number">
                        @error('medicalReportReference')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900">Vehicle Categories</h2>
                <p class="mt-1 text-sm text-slate-500">Only active categories are available for new enrollments.</p>
            </div>
            <div class="p-6">
                @if ($categories->isEmpty())
                    <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">There are no active categories available. Activate or create a vehicle category before registering this student.</p>
                @else
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @foreach ($categories as $category)
                            <label wire:key="registration-category-{{ $category->id }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50/40">
                                <input type="checkbox" wire:model.live="selectedCategoryIds" value="{{ $category->id }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">{{ $category->category_code }}</span>
                                        <span class="text-sm text-slate-700">{{ $category->category_name }}</span>
                                    </span>
                                    <span class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                                        <span>Fee: {{ number_format((float) $category->price, 2) }}</span>
                                        <span>Training: {{ number_format((float) $category->training_hours, 2) }} hrs</span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('selectedCategoryIds') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('selectedCategoryIds.*') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-6">
            <h2 class="text-lg font-bold text-slate-900">Registration Summary</h2>
            @if ($selectedCategories->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Select categories to see the course fee and training hours.</p>
            @else
                <ul class="mt-4 divide-y divide-blue-100">
                    @foreach ($selectedCategories as $category)
                        <li wire:key="summary-category-{{ $category->id }}" class="flex flex-col justify-between gap-1 py-3 text-sm sm:flex-row sm:gap-4">
                            <span class="font-medium text-slate-800">{{ $category->code }} — {{ $category->name }}</span>
                            <span class="text-slate-600">{{ number_format((float) $category->fee, 2) }} · {{ number_format((float) $category->hours, 2) }} hrs</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 grid grid-cols-1 gap-4 border-t border-blue-100 pt-4 sm:grid-cols-2">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Total Course Fee</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $totalCourseFee }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Total Required Training</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $totalTrainingHours }} hours</dd></div>
                </dl>
            @endif
            <p class="mt-4 text-xs text-slate-500">The agreed fee is saved as a snapshot. Planned installments are a schedule; only a recorded receipt counts as a payment.</p>
        </section>

        @can('payments.manage')
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6"><h2 class="text-lg font-bold text-slate-900">Payment Plan & Advance</h2><p class="mt-1 text-sm text-slate-500">Choose a schedule and optionally record money received today.</p></div>
            <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="payment-plan" class="mb-2 block text-sm font-medium text-slate-700">Installment schedule</label>
                    <select id="payment-plan" wire:model.live="paymentPlanType" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="full_payment">Full payment</option><option value="two_installments">2 installments</option><option value="three_installments">3 installments</option>
                    </select>
                    @error('paymentPlanType') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                        @foreach ($installmentAmounts as $index => $amountCents)
                            <span class="rounded-full bg-slate-100 px-3 py-1.5">Installment {{ $index + 1 }}: {{ number_format($amountCents / 100, 2) }}</span>
                        @endforeach
                    </div>
                </div>
                <div><label for="advance-amount" class="mb-2 block text-sm font-medium text-slate-700">Advance received</label><input id="advance-amount" type="number" min="0" step="0.01" wire:model="advanceAmount" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><p class="mt-1 text-xs text-slate-500">Enter 0.00 if no payment is collected now.</p>@error('advanceAmount') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                <div><label for="advance-date" class="mb-2 block text-sm font-medium text-slate-700">Payment date</label><input id="advance-date" type="date" max="{{ now()->toDateString() }}" wire:model="advancePaymentDate" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">@error('advancePaymentDate') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                <div><label for="advance-method" class="mb-2 block text-sm font-medium text-slate-700">Payment method</label><select id="advance-method" wire:model="advancePaymentMethod" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="card">Card</option><option value="other">Other</option></select>@error('advancePaymentMethod') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                <div><label for="advance-reference" class="mb-2 block text-sm font-medium text-slate-700">Reference number <span class="font-normal text-slate-400">(optional)</span></label><input id="advance-reference" type="text" maxlength="100" wire:model="advanceReference" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">@error('advanceReference') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                <div class="md:col-span-2"><label for="advance-notes" class="mb-2 block text-sm font-medium text-slate-700">Notes <span class="font-normal text-slate-400">(optional)</span></label><textarea id="advance-notes" rows="2" maxlength="2000" wire:model="advanceNotes" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></textarea>@error('advanceNotes') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror</div>
            </div>
        </section>
        @endcan

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ route('admin.students.index') }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="registerStudent,profilePhoto,medicalReport" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="registerStudent">Register Student</span>
                <span wire:loading wire:target="registerStudent">Registering…</span>
            </button>
        </div>
    </form>
</div>
