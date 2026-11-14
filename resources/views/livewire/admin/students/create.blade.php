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
                    <label for="medical-report" class="mb-2 block text-sm font-medium text-slate-700">Medical report <span class="font-normal text-slate-400">(optional, JPG/PNG, max 5 MB)</span></label>
                    <input id="medical-report" type="file" wire:model="medicalReport" accept="image/jpeg,image/png" class="block w-full rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:rounded-l-xl file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-slate-700">
                    @error('medicalReport') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    <span wire:loading wire:target="medicalReport" class="mt-2 block text-xs text-slate-400">Uploading report…</span>
                    @if ($medicalReport && in_array(strtolower($medicalReport->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'], true))
                        <img src="{{ $medicalReport->temporaryUrl() }}" alt="Medical report preview" class="mt-4 max-h-48 rounded-xl border border-slate-200 object-contain">
                    @endif
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
            <p class="mt-4 text-xs text-slate-500">This records agreed course fees only. Payment collection is handled separately.</p>
        </section>

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ route('admin.students.index') }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="registerStudent,medicalReport" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="registerStudent">Register Student</span>
                <span wire:loading wire:target="registerStudent">Registering…</span>
            </button>
        </div>
    </form>
</div>
