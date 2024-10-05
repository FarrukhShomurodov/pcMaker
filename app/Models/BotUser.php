<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'phone_number',
        'full_name',
        'step',
        'lang',
        'uname',
        'sms_code',
    ];

    public function assemblies(): HasMany
    {
        return $this->hasMany(Assembly::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function basket()
    {
        return $this->hasOne(Basket::class, 'bot_user_id', 'id');
    }
}
