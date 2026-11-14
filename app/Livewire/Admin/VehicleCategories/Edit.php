<?php

namespace App\Livewire\Admin\VehicleCategories;

use App\Models\VehicleCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public VehicleCategory $category;

    public string $category_code = '';

    public string $category_name = '';

    public string $description = '';

    public string $training_hours = '';

    public string $price = '0.00';

    public bool $is_active = true;

    public function mount(VehicleCategory $category): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('vehicle-categories.manage'), 403);

        $this->category = $category;
        $this->category_code = $category->category_code;
        $this->category_name = $category->category_name;
        $this->description = $category->description ?? '';
        $this->training_hours = $category->training_hours;
        $this->price = $category->price;
        $this->is_active = $category->is_active;
    }

    protected function rules(): array
    {
        return [
            'category_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicle_categories', 'category_code')->ignore($this->category->getKey()),
            ],
            'category_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'training_hours' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['present', 'boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'category_code.required' => 'Enter a category code.',
            'category_code.unique' => 'This category code is already in use.',
            'category_name.required' => 'Enter a category name.',
            'training_hours.required' => 'Enter the required training hours.',
            'training_hours.gt' => 'Training hours must be greater than zero.',
            'price.required' => 'Enter the course fee.',
            'price.min' => 'The course fee cannot be less than zero.',
        ];
    }

    public function updateCategory()
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('vehicle-categories.manage'), 403);

        $this->category_code = strtoupper(trim($this->category_code));
        $this->category_name = trim($this->category_name);
        $this->description = trim($this->description);

        $validated = $this->validate();
        $categoryId = $this->category->getKey();
        $validated['description'] = $validated['description'] !== '' ? $validated['description'] : null;

        DB::transaction(function () use ($validated, $categoryId): void {
            $category = VehicleCategory::query()
                ->lockForUpdate()
                ->findOrFail($categoryId);

            $category->fill($validated);
            $category->save();
            $this->category = $category;
        });

        session()->flash('success', 'Vehicle category updated successfully.');

        return $this->redirectRoute('admin.vehicle-categories.view', ['category' => $categoryId]);
    }

    public function render()
    {
        return view('livewire.admin.vehicle-categories.edit')
            ->layout('layouts.admin');
    }
}
