<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryCompatibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_category_id',
        'compatible_category_id'
    ];

    public function componentCategory(): BelongsTo
    {
        return $this->belongsTo(ComponentCategory::class, 'component_category_id');
    }

    public function compatibleCategory(): BelongsTo
    {
        return $this->belongsTo(ComponentCategory::class, 'compatible_category_id');
    }

    public static function areCompatible($type1, $type2)
    {
        return self::query()->where('component_category_id', $type1)
            ->where('compatible_category_id', $type2)
            ->exists();
    }
}
