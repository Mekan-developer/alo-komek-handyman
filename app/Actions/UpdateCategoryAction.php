<?php

namespace App\Actions;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Support\CategoryIcon;
use Illuminate\Http\UploadedFile;

class UpdateCategoryAction
{
    public function __construct(private readonly CategoryRepository $repository) {}

    /**
     * @param  array{name_ru: string, name_tk: string, is_active: bool, parent_id: int|null, icon_type?: string|null, icon?: string|null}  $data
     */
    public function handle(Category $category, array $data, ?UploadedFile $iconFile = null): Category
    {
        return $this->repository->update($category, CategoryIcon::apply($data, $iconFile, $category));
    }
}
