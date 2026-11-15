<?php

namespace App\Livewire\Admin\Coaches;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'phone' => ['required', 'string', 'max:20'],

            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Please enter the coach name.',

            'email.required' => 'Please enter an email address.',
            'email.unique' => 'This email address is already registered.',

            'phone.required' => 'Please enter a contact number.',

            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must contain at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    public function createCoach()
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {

            $coach = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'],
            ]);

            $coach->assignRole('Coach');
            try {
                AuditLog::create([
                    'user_id' => auth()->id(), 'user_name' => auth()->user()?->name,
                    'action' => 'created', 'module' => 'coaches', 'description' => 'Coach account created',
                    'related_type' => 'User', 'related_id' => $coach->getKey(),
                    'new_values' => ['name' => $coach->name, 'email' => $coach->email, 'phone' => $coach->phone, 'is_active' => $coach->is_active],
                    'ip_address' => request()->ip(), 'created_at' => now(),
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }

        });

        session()->flash('success', 'Coach registered successfully!');

        return $this->redirectRoute('admin.coaches.index');
    }

    public function render()
    {
        return view('livewire.admin.coaches.create')
            ->layout('layouts.admin');
    }
}
