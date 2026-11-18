@php
    $temporaryUpload = $temporaryUpload ?? null;
    $currentUrl = $currentUrl ?? null;
    $removeAction = $removeAction ?? null;
    $undoAction = $undoAction ?? null;
    $removePending = $removePending ?? false;
    $temporaryPreview = $temporaryUpload
        && in_array(strtolower($temporaryUpload->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'], true)
        ? $temporaryUpload->temporaryUrl()
        : null;
@endphp

<div x-data="privateStudentImageUpload(@js($field))" @dragover.prevent @drop.prevent="uploadDropped($event)" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="min-w-0 flex-1">
            <h3 class="text-sm font-bold text-slate-900">{{ $title }}</h3>
            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $description }}</p>
        </div>
        <button type="button" @click="startCamera()" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">
            <span aria-hidden="true">📷</span> Open camera
        </button>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-[auto_1fr] sm:items-center">
        <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
            @if ($temporaryPreview)
                <img src="{{ $temporaryPreview }}" alt="{{ $title }} preview" class="h-full w-full object-cover">
            @elseif ($currentUrl && ! $removePending)
                <img src="{{ $currentUrl }}" alt="Current {{ strtolower($title) }}" class="h-full w-full object-cover">
            @else
                <span class="px-2 text-center text-xs font-medium text-slate-400">No image selected</span>
            @endif
        </div>

        <div class="min-w-0">
            <label for="{{ $field }}-upload" class="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50/50">
                <span class="text-sm font-semibold text-slate-700">Drop an image here or browse files</span>
                <span class="mt-1 text-xs text-slate-500">JPG, JPEG, or PNG · up to 5 MB</span>
                <input id="{{ $field }}-upload" type="file" wire:model="{{ $field }}" accept="image/jpeg,image/png" capture="environment" class="sr-only">
            </label>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2">
                <span wire:loading wire:target="{{ $field }}" class="text-xs font-medium text-blue-700">Uploading image…</span>
                <span x-show="uploading" class="text-xs font-medium text-blue-700" style="display:none">Uploading image…</span>
                @if ($temporaryUpload)
                    <button type="button" @click="clearSelection()" class="text-xs font-semibold text-slate-600 underline">Clear selected image</button>
                @elseif ($currentUrl && $removeAction && ! $removePending)
                    <button type="button" wire:click="{{ $removeAction }}" wire:loading.attr="disabled" class="text-xs font-semibold text-red-600 underline">Remove current image</button>
                @endif
                @if ($removePending && $undoAction)
                    <span class="text-xs font-medium text-amber-700">Removal will be saved with the form.</span>
                    <button type="button" wire:click="{{ $undoAction }}" class="text-xs font-semibold text-blue-700 underline">Undo removal</button>
                @endif
            </div>
        </div>
    </div>

    @error($field)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
    <p x-show="uploadError" x-text="uploadError" role="alert" class="mt-2 text-xs text-red-600" style="display:none"></p>
    <p x-show="cameraError" x-text="cameraError" role="alert" class="mt-2 text-xs text-amber-700" style="display:none"></p>

    <div x-show="cameraOpen" x-cloak style="display:none" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 p-4" role="dialog" aria-modal="true" aria-label="Camera capture">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h4 class="font-semibold text-slate-900">Capture {{ strtolower($title) }}</h4>
                <button type="button" @click="closeCamera()" aria-label="Close camera" class="rounded-lg px-3 py-1 text-xl text-slate-500 hover:bg-slate-100">&times;</button>
            </div>
            <div class="bg-slate-950 p-3">
                <video x-ref="cameraVideo" autoplay playsinline class="max-h-[65vh] w-full rounded-xl bg-black object-contain"></video>
            </div>
            <div class="flex flex-wrap justify-between gap-3 p-5">
                <div class="flex gap-2">
                    <button type="button" @click="switchCamera()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Switch camera</button>
                    <button type="button" @click="closeCamera()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                </div>
                <button type="button" @click="capture()" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Capture photo</button>
            </div>
        </div>
    </div>
</div>
