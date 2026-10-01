<?php

namespace App\Support;

use App\Enums\CategoryIconType;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryIcon
{
    /**
     * Flat list of allowed preset icon keys (config + user-uploaded).
     *
     * @return array<int, string>
     */
    public static function presetKeys(): array
    {
        $configKeys = collect(config('service_icons', []))->flatten()->values()->all();

        return array_merge($configKeys, static::uploadedKeys());
    }

    /**
     * Keys of SVGs uploaded by admins — files named `u-*.svg` in the
     * service_icons disk (public/icons/services). These become reusable
     * shared assets shown in the icon picker for all categories.
     *
     * @return array<int, string>
     */
    public static function uploadedKeys(): array
    {
        try {
            return collect(Storage::disk('service_icons')->files())
                ->filter(fn (string $f) => str_starts_with($f, 'u-') && str_ends_with($f, '.svg'))
                ->map(fn (string $f) => basename($f, '.svg'))
                ->values()
                ->all();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Validation rules for `icon_file`, which depend on the chosen icon type:
     * a monochrome SVG for `custom`, a raster image for `image`.
     *
     * @return array<int, string>
     */
    public static function fileRules(mixed $iconType): array
    {
        return $iconType === CategoryIconType::Image->value
            ? ['file', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:10240']
            : ['file', 'extensions:svg', 'mimetypes:image/svg+xml,text/xml,text/plain', 'max:64'];
    }

    /**
     * Resolve the icon_type / icon columns from validated data and the uploaded
     * file. New uploads are stored to public/icons/services/ as permanent shared
     * assets (icon_type stays 'custom', key prefix 'u-'). Existing custom icons
     * (legacy storage path or new u- key) are kept when no new file is provided.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function apply(array $data, ?UploadedFile $file, ?Category $existing = null): array
    {
        unset($data['icon_file']);

        $type = $data['icon_type'] ?? null;
        $previousCustom = $existing?->icon_type === CategoryIconType::Custom ? $existing->icon : null;
        $previousImage = $existing?->icon_type === CategoryIconType::Image ? $existing->icon : null;

        // A raster image belongs to one category only: drop it once it is replaced or switched away from.
        if ($previousImage !== null && ($type !== CategoryIconType::Image->value || $file instanceof UploadedFile)) {
            Storage::disk('public')->delete($previousImage);
            $previousImage = null;
        }

        if ($type === CategoryIconType::Image->value) {
            if ($file instanceof UploadedFile) {
                $data['icon'] = static::storeImage($file);
            } elseif ($previousImage !== null) {
                $data['icon'] = $previousImage;
            } else {
                [$data['icon_type'], $data['icon']] = [null, null];
            }
        } elseif ($type === CategoryIconType::Custom->value) {
            if ($file instanceof UploadedFile) {
                $key = 'u-'.Str::uuid();
                Storage::disk('service_icons')->put("{$key}.svg", $file->getContent());
                $data['icon'] = $key;
            } elseif ($previousCustom !== null) {
                $data['icon'] = $previousCustom;
            } else {
                [$data['icon_type'], $data['icon']] = [null, null];
            }
        } elseif ($type !== CategoryIconType::Preset->value) {
            [$data['icon_type'], $data['icon']] = [null, null];
        }

        return $data;
    }

    /**
     * Store an uploaded raster icon on the public disk, shrunk to a WebP of at most 50 KB.
     * The original upload is removed; the returned path is relative to the public disk.
     */
    private static function storeImage(UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        $path = $file->store('category-images', 'public');
        $webpAbsolute = PhotoConverter::convertCategoryIcon($disk->path($path));

        $webpPath = 'category-images/'.basename($webpAbsolute);

        if ($webpPath !== $path) {
            $disk->delete($path);
        }

        return $webpPath;
    }

    /**
     * Remove a category's own icon file from disk, if any.
     * Raster images and legacy SVGs on the public Storage disk are removed —
     * new-style SVGs in service_icons are shared assets and not purged.
     */
    public static function purge(Category $category): void
    {
        if ($category->icon === null) {
            return;
        }

        if ($category->icon_type === CategoryIconType::Image) {
            Storage::disk('public')->delete($category->icon);

            return;
        }

        if ($category->icon_type !== CategoryIconType::Custom) {
            return;
        }

        // Legacy icons have a directory separator (e.g. 'category-icons/uuid.svg').
        // New-style keys are bare (e.g. 'u-uuid') and live in the shared service_icons dir.
        if (str_contains($category->icon, '/')) {
            Storage::disk('public')->delete($category->icon);
        }
    }
}
