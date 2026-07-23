<?php

namespace App\Actions;

use App\Models\Master;
use App\Repositories\MasterRepository;
use Illuminate\Http\UploadedFile;

class CreateMasterAction
{
    public function __construct(
        private readonly MasterRepository $repository,
        private readonly StoreMasterPhotoAction $storePhoto,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): Master
    {
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $data['photo'] = $this->storePhoto->handle($data['photo']);
        } else {
            unset($data['photo']);
        }

        return $this->repository->create($data);
    }
}
