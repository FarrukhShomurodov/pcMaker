<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasketItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'basket_id',
        'product_id',
        'assembly_id',
        'component_id',
        'admin_assembly_id',
        'product_count',
        'component_count',
        'price',
    ];

    public function basket(): BelongsTo
    {
        return $this->belongsTo(Basket::class);
    }
}
