<?php

namespace App\Actions;

use App\Exceptions\OrderException;
use App\Jobs\ConvertOrderPhotoJob;
use App\Models\Order;
use App\Models\OrderPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateClientOrderAction
{
    private const MAX_PHOTOS = 4;

    public function __construct(private readonly UpdateOrderAction $updateOrder) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     * @param  array<int, int>  $removePhotoIds
     */
    public function handle(Order $order, array $data, array $photos = [], array $removePhotoIds = []): Order
    {
        return DB::transaction(function () use ($order, $data, $photos, $removePhotoIds) {
            $order = $this->updateOrder->handle($order, $data);

            if ($removePhotoIds !== []) {
                $order->photos()
                    ->whereIn('id', $removePhotoIds)
                    ->get()
                    ->each(function (OrderPhoto $photo) {
                        Storage::disk('public')->delete($photo->path);
                        $photo->delete();
                    });
            }

            if ($order->photos()->count() + count($photos) > self::MAX_PHOTOS) {
                throw OrderException::tooManyPhotos();
            }

            foreach ($photos as $photo) {
                $path = $photo->store("orders/{$order->id}/problem", 'public');

                $record = $order->photos()->create([
                    'path' => $path,
                    'original_name' => $photo->getClientOriginalName(),
                    'status' => 'pending',
                ]);

                ConvertOrderPhotoJob::dispatch($record->id);
            }

            return $order->fresh();
        });
    }
}
