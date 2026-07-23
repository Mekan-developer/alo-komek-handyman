<?php

namespace App\Actions;

use App\Support\PhotoConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreMasterPhotoAction
{
    private const TARGET_WIDTH = 300;

    private const DIRECTORY = 'masters';

    /**
     * Store an uploaded master photo as a width-300 WebP on the public disk.
     * Returns the stored file path relative to the public disk root.
     */
    public function handle(UploadedFile $photo): string
    {
        $path = $photo->store(self::DIRECTORY, 'public');
        $absolutePath = Storage::disk('public')->path($path);

        try {
            $webpAbsolute = PhotoConverter::convertToWidth($absolutePath, self::TARGET_WIDTH);

            if ($webpAbsolute !== $absolutePath) {
                Storage::disk('public')->delete($path);
            }

            return ltrim(
                str_replace(Storage::disk('public')->path(''), '', $webpAbsolute),
                DIRECTORY_SEPARATOR
            );
        } catch (\Throwable) {
            return $path;
        }
    }
}
