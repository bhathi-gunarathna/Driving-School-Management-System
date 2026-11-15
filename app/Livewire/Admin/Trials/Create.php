<?php

namespace App\Livewire\Admin\Trials;

use App\Services\TrialService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public string $trial_date = '';
    public string $reporting_time = '';
    public string $location = '';
    public string $notes = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('trials.manage'), 403);
        $this->trial_date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'trial_date' => ['required', 'date', 'after_or_equal:today'],
            'reporting_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function createTrial()
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('trials.manage'), 403);
        $trial = app(TrialService::class)->create($user, $this->validate());
        session()->flash('success', 'Trial created successfully.');
        return $this->redirectRoute('admin.trials.view', ['trial' => $trial->getKey()]);
    }

    public function render() { return view('livewire.admin.trials.create')->layout('layouts.admin'); }
}
