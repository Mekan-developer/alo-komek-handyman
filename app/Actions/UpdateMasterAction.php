<?php

namespace App\Actions;

use App\Models\Master;
use App\Repositories\MasterRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateMasterAction
{
    public function __construct(
        private readonly MasterRepository $repository,
        private readonly StoreMasterPhotoAction $storePhoto,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(Master $master, array $data): Master
    {
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            if ($master->photo) {
                Storage::disk('public')->delete($master->photo);
            }

            $data['photo'] = $this->storePhoto->handle($data['photo']);
        } else {
            unset($data['photo']);
        }

        return $this->repository->update($master, $data);
    }
}
