<?php

namespace App\Actions;

use App\Exceptions\CategoryException;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Support\CategoryIcon;

class DeleteCategoryAction
{
    public function __construct(private readonly CategoryRepository $repository) {}

    /**
     * Deletes a category after checking it is not referenced by any order.
     * The `orders.category_id` FK is `restrictOnDelete`, so without this guard
     * the database would raise a raw integrity-constraint error.
     *
     * @throws CategoryException when orders still reference the category
     */
    public function handle(Category $category): void
    {
        $ordersCount = $this->repository->ordersCount($category);

        if ($ordersCount > 0) {
            throw CategoryException::hasOrders($ordersCount);
        }

        CategoryIcon::purge($category);
        $this->repository->delete($category);
    }
}
