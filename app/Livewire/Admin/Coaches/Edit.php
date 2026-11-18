<?php

namespace App\Livewire\Admin\Coaches;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use App\Services\UserSessionRevoker;

class Edit extends Component
{
    use PasswordValidationRules;

    public User $coach;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $is_active = true;

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public function mount(User $coach): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('coaches.manage'), 403);

        $this->coach = User::role('Coach')->findOrFail($coach->getKey());
        $this->name = $this->coach->name;
        $this->email = $this->coach->email;
        $this->phone = $this->coach->phone ?? '';
        $this->is_active = $this->coach->is_active;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->coach->getKey()),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'is_active' => ['present', 'boolean'],
            'password' => $this->passwordRules(required: false),
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Please enter the coach name.',
            'email.required' => 'Please enter an email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.required' => 'Please enter a contact number.',
        ];
    }

    public function updateCoach()
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('coaches.manage'), 403);

        $this->email = strtolower(trim($this->email));
        $this->name = trim($this->name);
        $this->phone = trim($this->phone);
        $this->password = $this->password === '' ? null : $this->password;
        $this->password_confirmation = $this->password_confirmation === '' ? null : $this->password_confirmation;

        $validated = $this->validate();
        $coachId = $this->coach->getKey();
        $authUser = $user;
        $passwordChanged = filled($validated['password'] ?? null);

        DB::transaction(function () use ($validated, $coachId, $authUser, $passwordChanged): void {
            $coach = User::role('Coach')
                ->lockForUpdate()
                ->findOrFail($coachId);

            if (! $validated['is_active'] && (string) $coach->getKey() === (string) $authUser->getAuthIdentifier()) {
                throw ValidationException::withMessages([
                    'is_active' => 'You cannot deactivate your own account.',
                ]);
            }

            $coach->name = $validated['name'];
            $coach->email = $validated['email'];
            $coach->phone = $validated['phone'];
            $coach->is_active = $validated['is_active'];

            if ($passwordChanged) {
                $coach->password = Hash::make($validated['password']);
            }

            $coach->save();

            if ($passwordChanged || (! $validated['is_active'] && $coach->wasChanged('is_active'))) {
                app(UserSessionRevoker::class)->revoke($coach);
            }

            $this->coach = $coach;
        });

        $this->password = '';
        $this->password_confirmation = '';

        if ($passwordChanged && (string) $coachId === (string) $authUser->getAuthIdentifier()) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            session()->flash('status', 'Password updated. Sign in again using the new password.');

            return $this->redirectRoute('login', navigate: false);
        }

        session()->flash('success', $passwordChanged
            ? 'Coach details and login credentials updated successfully.'
            : 'Coach details updated successfully.');

        return $this->redirectRoute('admin.coaches.view', ['coach' => $coachId]);
    }

    public function render()
    {
        return view('livewire.admin.coaches.edit')
            ->layout('layouts.admin');
    }
}
