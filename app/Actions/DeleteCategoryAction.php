<?php

namespace App\Actions;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Support\CategoryIcon;

class DeleteCategoryAction
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function handle(Category $category): void
    {
        CategoryIcon::purge($category);
        $this->repository->delete($category);
    }
}
