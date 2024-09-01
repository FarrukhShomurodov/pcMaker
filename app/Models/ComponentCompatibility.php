<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentCompatibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'component__with_id',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }

    public function compatibleWith(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_with_id');
    }

}
