<?php

namespace App\Livewire\Admin\Coaches;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Edit extends Component
{
    public User $coach;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $is_active = true;

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

        $validated = $this->validate();
        $coachId = $this->coach->getKey();
        $authUser = $user;

        DB::transaction(function () use ($validated, $coachId, $authUser): void {
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

            $coach->save();
            $this->coach = $coach;
        });

        session()->flash('success', 'Coach details updated successfully.');

        return $this->redirectRoute('admin.coaches.view', ['coach' => $coachId]);
    }

    public function render()
    {
        return view('livewire.admin.coaches.edit')
            ->layout('layouts.admin');
    }
}
