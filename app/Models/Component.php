<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'component_category_id',
        'component_type_id',
        'brand',
        'quantity',
        'price',
        'photos',
        'description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ComponentCategory::class, 'component_category_id', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class, 'component_type_id', 'id');
    }
}
