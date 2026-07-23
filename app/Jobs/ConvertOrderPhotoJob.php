<?php

namespace App\Jobs;

use App\Models\OrderPhoto;
use App\Support\PhotoConverter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ConvertOrderPhotoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $photoId) {}

    public function handle(): void
    {
        $photo = OrderPhoto::find($this->photoId);

        if ($photo === null || $photo->status === OrderPhoto::STATUS_DONE) {
            return;
        }

        $photo->update(['status' => OrderPhoto::STATUS_CONVERTING]);

        try {
            $absoluteOriginal = Storage::disk('public')->path($photo->path);
            $absoluteWebp = PhotoConverter::convert($absoluteOriginal);

            Storage::disk('public')->delete($photo->path);

            $webpRelative = ltrim(str_replace(
                Storage::disk('public')->path(''),
                '',
                $absoluteWebp
            ), DIRECTORY_SEPARATOR);

            $photo->update([
                'path' => $webpRelative,
                'status' => OrderPhoto::STATUS_DONE,
            ]);
        } catch (\Throwable $e) {
            $photo->update(['status' => OrderPhoto::STATUS_FAILED]);
            throw $e;
        }
    }
}
