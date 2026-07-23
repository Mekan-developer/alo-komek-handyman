<?php

namespace App\Models;

use App\Enums\CategoryIconType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'parent_id',
        'name_ru',
        'name_tk',
        'icon_type',
        'icon',
        'is_active',
    ];

    /** @var array<int, string> */
    protected $appends = ['icon_url', 'name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parent_id' => 'integer',
            'icon_type' => CategoryIconType::class,
        ];
    }

    /**
     * Localized category name resolved from the current app locale, falling back
     * to Russian. Lets all existing consumers keep reading `$category->name`.
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localized('name') ?? '');
    }

    /**
     * Pick the `{attribute}_{locale}` value for the active locale, falling back
     * to the Russian variant when the localized one is empty or not loaded.
     */
    private function localized(string $attribute): ?string
    {
        $locale = app()->getLocale();

        return $this->attributes["{$attribute}_{$locale}"]
            ?? $this->attributes["{$attribute}_ru"]
            ?? null;
    }

    /**
     * Resolved public URL of the category icon — preset asset or uploaded file.
     * Returns null when no icon is set. Consumed by web (CSS mask) and the mobile API.
     */
    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => match (true) {
            $this->icon_type === CategoryIconType::Preset && $this->icon !== null => asset("icons/services/{$this->icon}.svg"),
            // New-style custom: bare key (u-uuid) stored in public/icons/services/
            $this->icon_type === CategoryIconType::Custom && $this->icon !== null && ! str_contains($this->icon, '/') => asset("icons/services/{$this->icon}.svg"),
            // Legacy custom: path with directory separator on the public Storage disk
            $this->icon_type === CategoryIconType::Custom && $this->icon !== null => asset("storage/{$this->icon}"),
            default => null,
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function content(): HasOne
    {
        return $this->hasOne(CategoryContent::class);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
