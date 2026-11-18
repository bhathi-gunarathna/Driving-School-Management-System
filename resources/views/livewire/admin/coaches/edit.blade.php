<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <a href="{{ route('admin.coaches.view', $coach->id) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" aria-label="Back to coach profile">
            &larr;
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Coach</h1>
            <p class="mt-1 text-sm text-slate-500">Update {{ $coach->name }}’s profile and account settings.</p>
        </div>
    </div>

    <form wire:submit="updateCoach" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-900">Coach Information</h2>
            <p class="mt-1 text-sm text-slate-500">Coach account #{{ $coach->id }}</p>
        </div>

        <div class="space-y-8 p-6 sm:p-8">
            <section>
                <h3 class="mb-5 text-sm font-bold text-slate-800">Personal Information</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="coach-name" class="mb-2 block text-sm font-medium text-slate-700">Full name <span class="text-red-500">*</span></label>
                        <input id="coach-name" type="text" wire:model="name" autocomplete="name" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('name') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="coach-phone" class="mb-2 block text-sm font-medium text-slate-700">Contact number <span class="text-red-500">*</span></label>
                        <input id="coach-phone" type="tel" wire:model="phone" autocomplete="tel" required maxlength="20" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('phone') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-100 pt-7">
                <h3 class="mb-5 text-sm font-bold text-slate-800">Account Information</h3>
                <div>
                    <label for="coach-email" class="mb-2 block text-sm font-medium text-slate-700">Email address <span class="text-red-500">*</span></label>
                    <input id="coach-email" type="email" wire:model="email" autocomplete="email" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('email') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="border-t border-slate-100 pt-7" x-data="{ showPassword: false, showConfirmation: false }">
                <h3 class="text-sm font-bold text-slate-800">Login Credentials</h3>
                <p class="mb-5 mt-1 text-xs leading-5 text-slate-500">Leave both password fields blank to keep the current password. Saving a new password signs the Coach out of existing database-backed sessions.</p>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="coach-password" class="mb-2 block text-sm font-medium text-slate-700">New password</label>
                        <div class="relative">
                            <input id="coach-password" type="password" x-bind:type="showPassword ? 'text' : 'password'" wire:model="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-20 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-3 my-auto h-9 px-2 text-xs font-semibold text-blue-600 hover:text-blue-700" x-text="showPassword ? 'Hide' : 'Show'" aria-label="Show or hide new password"></button>
                        </div>
                        @error('password') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="coach-password-confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm new password</label>
                        <div class="relative">
                            <input id="coach-password-confirmation" type="password" x-bind:type="showConfirmation ? 'text' : 'password'" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-20 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" @click="showConfirmation = !showConfirmation" class="absolute inset-y-0 right-3 my-auto h-9 px-2 text-xs font-semibold text-blue-600 hover:text-blue-700" x-text="showConfirmation ? 'Hide' : 'Show'" aria-label="Show or hide password confirmation"></button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-100 pt-7">
                <h3 class="mb-5 text-sm font-bold text-slate-800">Account Status</h3>
                <label for="coach-active" class="flex cursor-pointer items-start gap-3">
                    <input id="coach-active" type="checkbox" wire:model="is_active" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>
                        <span class="block text-sm font-medium text-slate-800">Active account</span>
                        <span class="mt-1 block text-xs text-slate-500">An inactive coach cannot sign in.</span>
                    </span>
                </label>
                @error('is_active') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </section>
        </div>

        <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:px-8">
            <a href="{{ route('admin.coaches.view', $coach->id) }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</a>
            <button type="submit" wire:confirm="{{ $is_active ? 'Save these profile changes?' : 'Save changes and deactivate this Coach account?' }}" wire:loading.attr="disabled" wire:target="updateCoach" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="updateCoach">Save Changes</span>
                <span wire:loading wire:target="updateCoach">Saving…</span>
            </button>
        </div>
    </form>
</div>
