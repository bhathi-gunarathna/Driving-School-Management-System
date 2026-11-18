<?php

namespace App\Livewire\Admin\Expenses;

use App\Models\Expense;
use Livewire\Component;

class View extends Component
{
    public Expense $expense;

    public function mount(Expense $expense): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.view'), 403);
        $this->expense = $expense->load(['category', 'vehicle', 'createdBy']);
    }

    public function render()
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.view'), 403);

        return view('livewire.admin.expenses.view')->layout('layouts.admin');
    }
}
