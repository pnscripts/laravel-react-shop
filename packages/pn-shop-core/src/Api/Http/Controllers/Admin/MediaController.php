<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PnShop\Media\MediaLibrary;
use PnShop\Media\MediaPresenter;

class MediaController extends AdminController
{
    /**
     * Upload an image
     *
     * Multipart form with `file` (JPEG, PNG, WebP, AVIF or GIF) and optional `alt`. Returns
     * the media id to use in a product's `gallery`. Resized versions are made in the background.
     */
    public function store(Request $request, MediaLibrary $library): JsonResponse
    {
        Gate::authorize('content.media.manage');

        $data = $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', MediaLibrary::IMAGE_TYPES), 'max:'.MediaLibrary::MAX_UPLOAD_KB],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var UploadedFile $file */
        $file = $data['file'];
        // A random name: never trust the uploaded file name for paths.
        $path = $file->storeAs(MediaLibrary::directory(), Str::random(40).'.'.$file->guessExtension(), MediaLibrary::disk());

        abort_if($path === false, 500, __('The file could not be stored.'));

        $media = $library->register($path, Str::limit($file->getClientOriginalName(), 200, ''));

        if (isset($data['alt'])) {
            $media->update(['alt' => $data['alt']]);
        }

        return response()->json(['data' => MediaPresenter::present($media)], 201);
    }
}
