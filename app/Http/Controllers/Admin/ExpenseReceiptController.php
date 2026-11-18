<?php

namespace App\Http\Controllers\Admin;

use App\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseReceiptController
{
    public function __invoke(Expense $expense): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('expenses.view'), 403);

        $path = $expense->receipt_path;
        abort_unless(is_string($path) && preg_match('/\Aexpenses\/\d+\/[A-Za-z0-9._-]+\z/D', $path) === 1 && ! str_contains(basename($path), '..'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true), 404);

        return $disk->download($path, 'expense-receipt-'.$expense->getKey().'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
