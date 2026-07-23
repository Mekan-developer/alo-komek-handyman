<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\CategoryContent;
use App\Repositories\CategoryContentRepository;
use App\Support\PhotoConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpsertCategoryContentAction
{
    public function __construct(private readonly CategoryContentRepository $repository) {}

    /**
     * @param  array{title_ru: string, title_tk: string, description_ru: ?string, description_tk: ?string, price: ?string}  $data
     * @param  UploadedFile[]  $images
     * @param  int[]  $keepIds
     */
    public function handle(Category $category, array $data, array $images = [], array $keepIds = []): CategoryContent
    {
        $content = $this->repository->upsert($category, [
            'title_ru' => $data['title_ru'],
            'title_tk' => $data['title_tk'],
            'description_ru' => $data['description_ru'] ?? null,
            'description_tk' => $data['description_tk'] ?? null,
            'price' => $data['price'] ?? null,
        ]);

        $this->repository->deleteImagesExcept($content, $keepIds);

        foreach ($images as $image) {
            $path = $image->store('category-content', 'public');
            $absolutePath = Storage::disk('public')->path($path);

            try {
                $webpAbsolute = PhotoConverter::convertContent($absolutePath);
                Storage::disk('public')->delete($path);
                $webpRelative = ltrim(
                    str_replace(Storage::disk('public')->path(''), '', $webpAbsolute),
                    DIRECTORY_SEPARATOR
                );
                $this->repository->addImage($content, $webpRelative);
            } catch (\Throwable) {
                $this->repository->addImage($content, $path);
            }
        }

        return $content;
    }
}
