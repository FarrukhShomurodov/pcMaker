<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assembly extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_user_id',
        'total_price'
    ];

    public function components(): HasMany
    {
        return $this->hasMany(AssemblyComponent::class);
    }
}
