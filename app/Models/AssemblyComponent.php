<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'assembly_id',
    ];
}
