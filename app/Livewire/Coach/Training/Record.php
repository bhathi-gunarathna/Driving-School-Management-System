<?php

namespace App\Livewire\Coach\Training;

use App\Models\StudentVehicleCategory;
use App\Models\User;
use App\Services\TrainingService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Record extends Component
{
    public StudentVehicleCategory $studentEnrollment;
    public string $training_date = '';
    public string $start_time = '';
    public string $end_time = '';
    public string $duration_minutes = '';
    public string $notes = '';

    public function mount(int $enrollment): void
    {
        $coach = $this->authorizeCoach();
        $this->studentEnrollment = StudentVehicleCategory::query()->with(['student', 'vehicleCategory'])
            ->where('status', 'active')->whereHas('student', fn ($q) => $q->where('status', 'active'))
            ->whereHas('coachAssignments', fn ($q) => $q->where('coach_id', $coach->id)->where('status', 'active'))
            ->findOrFail($enrollment);
        $this->training_date = now()->toDateString();
    }

    private function authorizeCoach(): User
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Coach') && $user->is_active && $user->can('training.record'), 403);
        return $user;
    }

    protected function rules(): array
    {
        return [
            'training_date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function record()
    {
        $coach = $this->authorizeCoach();
        $data = $this->validate();
        $hasStart = filled($data['start_time']);
        $hasEnd = filled($data['end_time']);
        if ($hasStart xor $hasEnd) {
            $this->addError('end_time', 'Enter both start and end times, or leave both blank.');
            return null;
        }
        if ($hasStart && $hasEnd) {
            $start = Carbon::createFromFormat('!H:i', $data['start_time']);
            $end = Carbon::createFromFormat('!H:i', $data['end_time']);
            $duration = (int) (($end->timestamp - $start->timestamp) / 60);
            if ($duration < 1) {
                $this->addError('end_time', 'End time must be later than start time.');
                return null;
            }
        } else {
            $duration = (int) ($data['duration_minutes'] ?? 0);
            if ($duration < 1) {
                $this->addError('duration_minutes', 'Enter a duration greater than zero, or provide both times.');
                return null;
            }
        }

        $session = app(TrainingService::class)->recordSession($this->studentEnrollment->getKey(), $coach, [
            ...$data, 'duration_minutes' => $duration,
        ]);
        session()->flash('success', 'Training session recorded and added to the permanent history.');
        return $this->redirectRoute('coach.training.index');
    }

    public function render()
    {
        $this->authorizeCoach();
        return view('livewire.coach.training.record', ['enrollment' => $this->studentEnrollment])->layout('layouts.coach');
    }
}
