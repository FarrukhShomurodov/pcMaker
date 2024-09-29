<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeCompatibility extends Model
{
    use HasFactory;

    protected $fillable = ['component_type_id', 'compatible_type_id'];

    public function componentType(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class, 'component_type_id');
    }

    public function compatibleType(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class, 'compatible_type_id');
    }

    public static function areCompatible($type1, $type2)
    {
        return self::query()->where('component_type_id', $type1)
            ->where('compatible_type_id', $type2)
            ->exists();
    }
}
