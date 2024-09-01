<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Basket extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_user_id',
        'total_price'
    ];

    public function basketItems()
    {
        return $this->hasMany(BasketItem::class, 'basket_id', 'id');
    }
}
