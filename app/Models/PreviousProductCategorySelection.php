<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreviousProductCategorySelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_user_id',
        'product_category_id'
    ];
}
