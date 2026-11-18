<?php

namespace App\Livewire\Admin\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class Edit extends Component
{
    use WithFileUploads;

    public Expense $expense;
    public string $expense_category_id = '';
    public ?string $vehicle_id = '';
    public string $title = '';
    public string $amount = '';
    public string $expense_date = '';
    public string $payment_method = 'cash';
    public string $paid_to = '';
    public string $description = '';
    public $receipt = null;
    public bool $removeReceipt = false;

    public function mount(Expense $expense): void
    {
        $this->authorizeEdit();
        $this->expense = $expense;
        $this->expense_category_id = (string) $expense->expense_category_id;
        $this->vehicle_id = (string) ($expense->vehicle_id ?? '');
        $this->title = $expense->title;
        $this->amount = $expense->amount;
        $this->expense_date = $expense->expense_date->toDateString();
        $this->payment_method = $expense->payment_method;
        $this->paid_to = $expense->paid_to ?? '';
        $this->description = $expense->description ?? '';
    }

    protected function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->expense->expense_category_id))],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'title' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'string', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/', 'gt:0'],
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(Expense::PAYMENT_METHODS)],
            'paid_to' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'removeReceipt' => ['boolean'],
        ];
    }

    public function save()
    {
        $this->authorizeEdit();
        $this->title = trim($this->title);
        $this->paid_to = trim($this->paid_to);
        $this->description = trim($this->description);
        if ($this->vehicle_id === '') $this->vehicle_id = null;
        $validated = $this->validate();
        $validated['amount'] = Money::fromCents(Money::toCents($validated['amount']));
        $validated['vehicle_id'] = $validated['vehicle_id'] ?: null;
        $validated['paid_to'] = $validated['paid_to'] === '' ? null : $validated['paid_to'];
        $validated['description'] = $validated['description'] === '' ? null : $validated['description'];
        unset($validated['receipt'], $validated['removeReceipt']);

        $oldPath = $this->expense->receipt_path;
        $storedPath = null;
        try {
            DB::transaction(function () use ($validated, &$storedPath): void {
                $expense = Expense::query()->lockForUpdate()->findOrFail($this->expense->getKey());
                if ($this->receipt) {
                    $storedPath = $this->receipt->store('expenses/'.$expense->getKey(), 'local');
                    if (! is_string($storedPath)) throw new \RuntimeException('The receipt could not be stored.');
                    $validated['receipt_path'] = $storedPath;
                } elseif ($this->removeReceipt) {
                    $validated['receipt_path'] = null;
                }
                $expense->update($validated);
                $this->expense = $expense->fresh(['category', 'vehicle', 'createdBy']);
            });
        } catch (Throwable $exception) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        if ($oldPath && ($storedPath || $this->removeReceipt)) Storage::disk('local')->delete($oldPath);
        session()->flash('success', 'Expense updated successfully.');

        return $this->redirectRoute('admin.expenses.view', ['expense' => $this->expense->getKey()]);
    }

    private function authorizeEdit(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.edit'), 403);
    }

    public function render()
    {
        $this->authorizeEdit();

        return view('livewire.admin.expenses.edit', [
            'categories' => ExpenseCategory::query()->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->expense->expense_category_id))->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(),
            'paymentMethods' => Expense::PAYMENT_METHODS,
        ])->layout('layouts.admin');
    }
}
