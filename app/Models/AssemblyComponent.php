<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'assembly_id',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class, 'assembly_id');
    }
}
