<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\CategoryContent;
use App\Models\CategoryContentImage;
use Illuminate\Support\Facades\Storage;

class CategoryContentRepository
{
    public function findByCategory(Category $category): ?CategoryContent
    {
        return $category->content()->with('images')->first();
    }

    public function upsert(Category $category, array $data): CategoryContent
    {
        /** @var CategoryContent $content */
        $content = $category->content()->firstOrNew([]);
        $content->fill($data)->save();

        return $content;
    }

    public function addImage(CategoryContent $content, string $path, int $sortOrder = 0): CategoryContentImage
    {
        return $content->images()->create([
            'path' => $path,
            'sort_order' => $sortOrder,
        ]);
    }

    /** Delete images whose IDs are NOT in the given keep list. */
    public function deleteImagesExcept(CategoryContent $content, array $keepIds): void
    {
        $content->images()
            ->when(count($keepIds) > 0, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->get()
            ->each(function (CategoryContentImage $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            });
    }
}
