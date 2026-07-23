<?php

namespace App\Jobs;

use App\Models\OrderTaskPhoto;
use App\Support\PhotoConverter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ConvertTaskPhotoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $photoId,
    ) {}

    public function handle(): void
    {
        $photo = OrderTaskPhoto::find($this->photoId);

        if ($photo === null || $photo->status === OrderTaskPhoto::STATUS_DONE) {
            return;
        }

        $photo->update(['status' => OrderTaskPhoto::STATUS_CONVERTING]);

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
                'status' => OrderTaskPhoto::STATUS_DONE,
            ]);
        } catch (\Throwable $e) {
            $photo->update(['status' => OrderTaskPhoto::STATUS_FAILED]);
            throw $e;
        }
    }
}
