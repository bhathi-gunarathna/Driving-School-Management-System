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

class Create extends Component
{
    use WithFileUploads;

    public string $expense_category_id = '';
    public ?string $vehicle_id = '';
    public string $title = '';
    public string $amount = '';
    public string $expense_date = '';
    public string $payment_method = 'cash';
    public string $paid_to = '';
    public string $description = '';
    public $receipt = null;

    public function mount(): void
    {
        $this->authorizeCreate();
        $this->expense_date = today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('is_active', true)],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'title' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'string', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/', 'gt:0'],
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(Expense::PAYMENT_METHODS)],
            'paid_to' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    public function save()
    {
        $this->authorizeCreate();
        $this->title = trim($this->title);
        $this->paid_to = trim($this->paid_to);
        $this->description = trim($this->description);
        if ($this->vehicle_id === '') $this->vehicle_id = null;
        $validated = $this->validate();
        $validated['amount'] = Money::fromCents(Money::toCents($validated['amount']));
        $validated['vehicle_id'] = $validated['vehicle_id'] ?: null;
        $validated['paid_to'] = $validated['paid_to'] === '' ? null : $validated['paid_to'];
        $validated['description'] = $validated['description'] === '' ? null : $validated['description'];
        $validated['created_by'] = auth()->id();
        unset($validated['receipt']);

        $storedPath = null;
        try {
            $expense = DB::transaction(function () use ($validated, &$storedPath): Expense {
                $expense = Expense::query()->create($validated);
                if ($this->receipt) {
                    $storedPath = $this->receipt->store('expenses/'.$expense->getKey(), 'local');
                    if (! is_string($storedPath)) {
                        throw new \RuntimeException('The receipt could not be stored.');
                    }
                    $expense->update(['receipt_path' => $storedPath]);
                }

                return $expense;
            });
        } catch (Throwable $exception) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        session()->flash('success', 'Expense recorded successfully.');

        return $this->redirectRoute('admin.expenses.view', ['expense' => $expense->getKey()]);
    }

    private function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.create'), 403);
    }

    public function render()
    {
        $this->authorizeCreate();

        return view('livewire.admin.expenses.create', [
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('registration_number')->get(),
            'paymentMethods' => Expense::PAYMENT_METHODS,
        ])->layout('layouts.admin');
    }
}
