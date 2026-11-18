<?php

namespace App\Livewire\Admin\Vehicles;

use App\Services\VehicleDocumentExpiryService;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class Expiry extends Component
{
    use WithPagination;

    public string $search = '';
    public string $documentType = 'all';
    public string $expiryFilter = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedDocumentType(): void
    {
        if (! in_array($this->documentType, ['all', 'insurance', 'revenue_license'], true)) {
            $this->documentType = 'all';
        }
        $this->resetPage();
    }

    public function updatedExpiryFilter(): void
    {
        if (! in_array($this->expiryFilter, ['all', 'expiring', 'expired', 'valid'], true)) {
            $this->expiryFilter = 'all';
        }
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->validateOnly('dateFrom', ['dateFrom' => ['nullable', 'date_format:Y-m-d']]);
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->validateOnly('dateTo', ['dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom']]);
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.view'), 403);
    }

    public function render()
    {
        $this->authorizeView();
        $schoolTimezone = SystemSetting::query()->value('timezone');
        if (! is_string($schoolTimezone) || ! in_array($schoolTimezone, timezone_identifiers_list(), true)) {
            $schoolTimezone = config('app.timezone', 'Asia/Colombo');
        }
        $today = CarbonImmutable::now($schoolTimezone)->startOfDay();
        $through = $today->addDays(30)->toDateString();
        $dateRangeValid = Validator::make(
            ['dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo],
            ['dateFrom' => ['nullable', 'date_format:Y-m-d'], 'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom']]
        )->passes();

        $documents = DB::query()->fromSub(app(VehicleDocumentExpiryService::class)->documents(), 'vehicle_documents')
            ->select(['vehicle_id', 'registration_number', 'vehicle_status', 'document_type', 'expiry_date'])
            ->when(trim($this->search) !== '', fn ($query) => $query->where('registration_number', 'like', '%'.trim($this->search).'%'))
            ->when($this->documentType !== 'all', fn ($query) => $query->where('document_type', $this->documentType));

        if ($this->expiryFilter === 'expiring') {
            $documents->whereDate('expiry_date', '>=', $today->toDateString())->whereDate('expiry_date', '<=', $through);
        } elseif ($this->expiryFilter === 'expired') {
            $documents->whereDate('expiry_date', '<', $today->toDateString());
        } elseif ($this->expiryFilter === 'valid') {
            $documents->whereDate('expiry_date', '>', $through);
        }

        if ($dateRangeValid && $this->dateFrom !== '') {
            $documents->whereDate('expiry_date', '>=', $this->dateFrom);
        }
        if ($dateRangeValid && $this->dateTo !== '') {
            $documents->whereDate('expiry_date', '<=', $this->dateTo);
        }

        $documents = $documents->orderBy('expiry_date')->orderBy('registration_number')->paginate(15);
        $documents->getCollection()->transform(function (object $document) use ($today, $schoolTimezone): object {
            $document->days_remaining = (int) $today->diffInDays(CarbonImmutable::parse($document->expiry_date, $schoolTimezone)->startOfDay(), false);

            return $document;
        });

        return view('livewire.admin.vehicles.expiry', [
            'documents' => $documents,
            'today' => $today,
            'through' => $through,
        ])->layout('layouts.admin');
    }
}
