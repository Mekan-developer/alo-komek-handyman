<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Jobs\ConvertTaskPhotoJob;
use App\Models\Master;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use App\OrderStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReplaceTaskPhotoAction
{
    /**
     * Swaps the file behind an existing photo slot — the master reshoots a blurry
     * before/after shot without burning one of the two slots per type.
     *
     * The `type` stays fixed: this replaces a file, not the slot it lives in.
     * Resetting the status back to `pending` is what re-arms {@see ConvertTaskPhotoJob},
     * which skips photos already marked `done`.
     */
    public function handle(Master $master, OrderTask $task, OrderTaskPhoto $photo, UploadedFile $file): OrderTask
    {
        if ($task->order->master_id !== $master->id) {
            throw OrderException::taskNotOwned();
        }

        if ($task->order->status !== OrderStatus::InProgress) {
            throw OrderException::taskPhotoNotEditable();
        }

        $path = $file->store("orders/{$task->order_id}/tasks/{$task->id}/{$photo->type}", 'public');

        $previousPath = $photo->path;

        $photo->update([
            'path' => $path,
            'status' => OrderTaskPhoto::STATUS_PENDING,
        ]);

        Storage::disk('public')->delete($previousPath);

        ConvertTaskPhotoJob::dispatch($photo->id);

        return $task->load(['beforePhotos', 'afterPhotos']);
    }
}
