<?php

namespace App\Actions;

use App\Jobs\ConvertTaskPhotoJob;
use App\Models\Master;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use Illuminate\Http\UploadedFile;

class UploadTaskPhotoAction
{
    public const MAX_PHOTOS_PER_TYPE = 2;

    /** @param 'before'|'after' $type */
    public function handle(Master $master, OrderTask $task, string $type, UploadedFile $photo): OrderTask
    {
        if ($task->order->master_id !== $master->id) {
            throw new \DomainException('This task does not belong to you.');
        }

        $count = $task->photos()->where('type', $type)->count();

        if ($count >= self::MAX_PHOTOS_PER_TYPE) {
            throw new \DomainException('Maximum '.self::MAX_PHOTOS_PER_TYPE." {$type} photos allowed per task.");
        }

        $path = $photo->store("orders/{$task->order_id}/tasks/{$task->id}/{$type}", 'public');

        $taskPhoto = $task->photos()->create([
            'type' => $type,
            'path' => $path,
            'status' => OrderTaskPhoto::STATUS_PENDING,
        ]);

        ConvertTaskPhotoJob::dispatch($taskPhoto->id);

        return $task->load(['beforePhotos', 'afterPhotos']);
    }
}
