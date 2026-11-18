<?php

namespace App\Http\Controllers\Admin;

use App\Services\ExpenseReports;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseExportController
{
    public function __invoke(Request $request, ExpenseReports $reports): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.view'), 403);

        $filters = $request->only(['startDate', 'endDate', 'category', 'vehicle', 'method', 'search']);
        $group = $request->string('group')->toString();
        abort_unless(in_array($group, ['daily', 'monthly', 'yearly', 'category', 'vehicle'], true), 422);
        $rows = $reports->grouped($filters, $group);

        return response()->streamDownload(function () use ($rows, $group): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Group', 'Expenses', 'Total (LKR)']);
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->safeCsv(...), [
                    $row->label,
                    $row->expense_count,
                    number_format((float) $row->total, 2, '.', ''),
                ]));
            }
            fclose($output);
        }, 'expenses-'.$group.'-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsv(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
