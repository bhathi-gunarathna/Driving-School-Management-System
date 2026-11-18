<?php

namespace App\Livewire\Admin\Expenses;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Categories extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public bool $is_active = true;

    public function mount(): void { $this->authorizeManage(); }

    public function editCategory(int $id): void
    {
        $this->authorizeManage();
        $category = ExpenseCategory::query()->findOrFail($id);
        $this->editingId = $category->getKey();
        $this->name = $category->name;
        $this->is_active = $category->is_active;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->authorizeManage();
        $this->reset('editingId', 'name');
        $this->is_active = true;
        $this->resetValidation();
    }

    public function saveCategory(): void
    {
        $this->authorizeManage();
        $this->name = trim($this->name);
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('expense_categories', 'name')->ignore($this->editingId)],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            if ($this->editingId) {
                ExpenseCategory::query()->lockForUpdate()->findOrFail($this->editingId)->update($validated);
            } else {
                ExpenseCategory::query()->create($validated);
            }
        });

        session()->flash('success', $this->editingId ? 'Expense category updated.' : 'Expense category created.');
        $this->cancelEdit();
    }

    public function deleteCategory(int $id): void
    {
        $this->authorizeManage();
        $this->resetValidation('categoryDelete');
        DB::transaction(function () use ($id): void {
            $category = ExpenseCategory::query()->lockForUpdate()->findOrFail($id);
            if ($category->expenses()->exists()) {
                $this->addError('categoryDelete', 'This category is used by expenses and cannot be deleted. Deactivate it instead.');
                return;
            }
            $category->delete();
        });
        if (! $this->getErrorBag()->has('categoryDelete')) session()->flash('success', 'Expense category deleted.');
    }

    private function authorizeManage(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.manage_categories'), 403);
    }

    public function render()
    {
        $this->authorizeManage();

        return view('livewire.admin.expenses.categories', [
            'categories' => ExpenseCategory::query()->withCount('expenses')->orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
