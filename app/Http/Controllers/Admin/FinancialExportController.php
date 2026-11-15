<?php

namespace App\Http\Controllers\Admin;

use App\Models\StudentPaymentPlan;
use App\Services\FinancialReports;
use App\Support\FinancialReportFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialExportController
{
    private function authorizeExport(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('financial-reports.export'), 403);
    }

    public function transactions(Request $request): StreamedResponse
    {
        $this->authorizeExport();
        $query = app(FinancialReports::class)->payments($request->only(['period', 'startDate', 'endDate', 'method', 'search', 'sort', 'direction']));

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Receipt Number', 'Payment Date', 'Student Number', 'Student Name', 'Payment Amount (LKR)', 'Payment Method', 'Reference Number', 'Received By']);
            $query->chunk(500, function ($payments) use ($output): void {
                foreach ($payments as $payment) {
                    fputcsv($output, array_map($this->safeCsv(...), [
                        $payment->receipt_number,
                        $payment->payment_date->format('Y-m-d'),
                        $payment->student?->student_number,
                        $payment->student?->full_name,
                        number_format((float) $payment->amount, 2, '.', ''),
                        str($payment->payment_method)->replace('_', ' ')->title()->toString(),
                        $payment->reference_number,
                        $payment->receivedBy?->name,
                    ]));
                }
            });
            fclose($output);
        }, 'payment-transactions.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function daily(Request $request): StreamedResponse
    {
        $this->authorizeExport();
        $rows = app(FinancialReports::class)->dailyIncome($request->only(['period', 'startDate', 'endDate', 'method', 'search']));
        return $this->summaryCsv('daily-income.csv', ['Date', 'Transactions', 'Income (LKR)'], $rows, 'report_date');
    }

    public function monthly(Request $request): StreamedResponse
    {
        $this->authorizeExport();
        $rows = app(FinancialReports::class)->monthlyIncome($request->only(['period', 'startDate', 'endDate', 'method', 'search']));
        return $this->summaryCsv('monthly-income.csv', ['Month', 'Transactions', 'Income (LKR)'], $rows, 'report_month');
    }

    public function outstanding(Request $request): StreamedResponse
    {
        $this->authorizeExport();
        $query = app(FinancialReports::class)->outstanding($request->only(['search', 'status', 'direction']));

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Student Number', 'Student Name', 'Contact Number', 'Total Course Fee (LKR)', 'Total Paid (LKR)', 'Outstanding Balance (LKR)', 'Payment Plan', 'Payment Status']);
            $query->chunk(500, function ($plans) use ($output): void {
                foreach ($plans as $plan) {
                    fputcsv($output, array_map($this->safeCsv(...), [
                        $plan->student?->student_number,
                        $plan->student?->full_name,
                        $plan->student?->contact_number,
                        number_format((float) $plan->total_agreed_fee, 2, '.', ''),
                        number_format((float) $plan->total_amount_paid, 2, '.', ''),
                        number_format((float) $plan->outstanding_balance, 2, '.', ''),
                        str($plan->plan_type)->replace('_', ' ')->title()->toString(),
                        str($plan->payment_status)->replace('_', ' ')->title()->toString(),
                    ]));
                }
            });
            fclose($output);
        }, 'outstanding-payments.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array<int, string> $headings */
    private function summaryCsv(string $filename, array $headings, iterable $rows, string $dateField): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows, $dateField): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headings);
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->safeCsv(...), [
                    $row->{$dateField},
                    $row->transaction_count,
                    number_format((float) $row->income, 2, '.', ''),
                ]));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsv(mixed $value): string
    {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s]*[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
