<?php

namespace App\Models;

use Database\Factories\CategoryContentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryContent extends Model
{
    /** @use HasFactory<CategoryContentFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'category_id',
        'title_ru',
        'title_tk',
        'description_ru',
        'description_tk',
        'price',
    ];

    /** @var array<int, string> */
    protected $appends = ['title', 'description'];

    /**
     * Localized title resolved from the current app locale, falling back to
     * Russian. Lets the mobile API and existing consumers read `$content->title`.
     */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->localized('title'));
    }

    /** Localized description resolved from the current app locale (Russian fallback). */
    protected function description(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->localized('description'));
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CategoryContentImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
