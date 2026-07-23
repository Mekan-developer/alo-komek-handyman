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

        if ($type === CategoryIconType::Custom->value) {
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
     * Remove a category's custom icon file from disk, if any.
     * Only legacy icons stored on the public Storage disk are removed —
     * new-style icons in service_icons are shared assets and not purged.
     */
    public static function purge(Category $category): void
    {
        if ($category->icon_type !== CategoryIconType::Custom || $category->icon === null) {
            return;
        }

        // Legacy icons have a directory separator (e.g. 'category-icons/uuid.svg').
        // New-style keys are bare (e.g. 'u-uuid') and live in the shared service_icons dir.
        if (str_contains($category->icon, '/')) {
            Storage::disk('public')->delete($category->icon);
        }
    }
}
