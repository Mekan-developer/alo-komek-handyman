<?php

namespace App\Models;

use Database\Factories\OblastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oblast extends Model
{
    /** @use HasFactory<OblastFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
