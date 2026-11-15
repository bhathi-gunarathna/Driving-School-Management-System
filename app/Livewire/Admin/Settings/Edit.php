<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class Edit extends Component
{
    use WithFileUploads;

    public string $school_name = '';
    public string $address = '';
    public string $contact_number = '';
    public string $email = '';
    public string $website = '';
    public string $timezone = 'Asia/Colombo';
    public string $currency = 'LKR';
    public string $date_format = 'd M Y';
    public string $receipt_prefix = 'DS-RCPT';
    public string $student_number_prefix = 'DS';
    public ?TemporaryUploadedFile $logo = null;

    public function mount(): void
    {
        $this->authorizeSettings();
        $settings = SystemSetting::current();
        foreach (['school_name', 'address', 'contact_number', 'email', 'website', 'timezone', 'currency', 'date_format', 'receipt_prefix', 'student_number_prefix'] as $field) {
            $this->{$field} = (string) ($settings->{$field} ?? '');
        }
    }

    private function authorizeSettings(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('settings.manage'), 403);
    }

    protected function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:3000'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'date_format' => ['required', 'in:d M Y,Y-m-d,m/d/Y,d/m/Y'],
            'receipt_prefix' => ['required', 'alpha_dash', 'max:30'],
            'student_number_prefix' => ['required', 'alpha_dash', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function saveSettings(): void
    {
        $this->authorizeSettings();
        $data = $this->validate();
        $uploadedPath = null;
        if ($this->logo) {
            $uploadedPath = $this->logo->store('school/logo', 'public');
            if (! is_string($uploadedPath)) {
                $this->addError('logo', 'The logo could not be saved. Please try again.');
                return;
            }
        }

        try {
            DB::transaction(function () use ($data, $uploadedPath): void {
                $settings = SystemSetting::query()->first() ?? new SystemSetting();
                $settings->fill(collect($data)->except('logo')->all());
                if ($uploadedPath) {
                    $settings->logo_path = $uploadedPath;
                }
                $settings->save();
            });
        } catch (Throwable $exception) {
            if ($uploadedPath) {
                Storage::disk('public')->delete($uploadedPath);
            }
            throw $exception;
        }

        session()->flash('success', 'System settings have been updated.');
        $this->redirectRoute('admin.settings.edit');
    }

    public function render()
    {
        $this->authorizeSettings();
        return view('livewire.admin.settings.edit', [
            'settings' => SystemSetting::current(),
            'timezones' => timezone_identifiers_list(),
        ])->layout('layouts.admin');
    }
}
