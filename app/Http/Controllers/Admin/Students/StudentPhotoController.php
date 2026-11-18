<?php

namespace App\Http\Controllers\Admin\Students;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPhotoController
{
    public function __invoke(Student $student): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('students.view_all'), 403);

        $path = $student->profile_photo_path;
        abort_unless(is_string($path)
            && preg_match('/\\Astudent-photos\\/[A-Za-z0-9._-]+\\z/D', $path) === 1
            && ! str_contains(basename($path), '..'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png'], true), 404);

        return $disk->response(
            $path,
            'student-photo-'.$student->student_number,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }
}
