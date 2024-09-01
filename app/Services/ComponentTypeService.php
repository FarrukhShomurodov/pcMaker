<?php

namespace App\Services;

use App\Models\ComponentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ComponentTypeService
{
    public function store(array $validated): Model|Builder
    {
        return ComponentType::query()->create($validated);
    }

    public function update(array $validated, ComponentType $type): Model|Builder
    {
        $type->update($validated);
        return $type->refresh();
    }

    public function delete(ComponentType $type): JsonResponse
    {
        $type->delete();
        return response()->json('', 200);
    }
}
