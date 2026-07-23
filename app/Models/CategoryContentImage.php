<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryContentImage extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'category_content_id',
        'path',
        'sort_order',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(CategoryContent::class, 'category_content_id');
    }
}
