<?php

namespace App\Http\Controllers\Admin\Students;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalReportController
{
    public function __invoke(Student $student): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.view_all'), 403);

        $path = $student->medical_report_path;
        abort_unless($this->isSafeManagedPath($path, 'medical-reports'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png'], true), 404);

        return $disk->response(
            $path,
            'medical-report-'.$student->student_number,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }

    private function isSafeManagedPath(?string $path, string $directory): bool
    {
        return is_string($path)
            && preg_match('/\\A'.preg_quote($directory, '/').'\\/[A-Za-z0-9._-]+\\z/D', $path) === 1
            && ! str_contains(basename($path), '..');
    }
}
