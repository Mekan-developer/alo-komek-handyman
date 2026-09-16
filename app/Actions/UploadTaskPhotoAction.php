<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Jobs\ConvertTaskPhotoJob;
use App\Models\Master;
use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use App\OrderStatus;
use Illuminate\Http\UploadedFile;

class UploadTaskPhotoAction
{
    public const MAX_PHOTOS_PER_TYPE = 2;

    /**
     * Store a new before/after photo on a task that belongs to the master.
     *
     * @param  'before'|'after'  $type
     *
     * @throws OrderException
     */
    public function handle(Master $master, OrderTask $task, string $type, UploadedFile $photo): OrderTask
    {
        if ($task->order->master_id !== $master->id) {
            throw OrderException::taskNotOwned();
        }

        if ($task->order->status !== OrderStatus::InProgress) {
            throw OrderException::taskPhotoNotEditable();
        }

        $count = $task->photos()->where('type', $type)->count();

        if ($count >= self::MAX_PHOTOS_PER_TYPE) {
            throw OrderException::taskPhotoLimitReached($type);
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
