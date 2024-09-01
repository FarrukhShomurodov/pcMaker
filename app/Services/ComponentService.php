<?php

namespace App\Services;

use App\Models\Component;
use App\Traits\PhotoTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ComponentService
{
    use PhotoTrait;

    public function store(array $validated): Model|Builder
    {
        if (isset($validated['photos'])) {
            $validated['photos'] = $this->storePhotos('component_photos');
        }

        return Component::query()->create($validated);
    }

    public function update(array $validated, Component $component): Model|Builder
    {
        if (isset($validated['photos'])) {
            $validated['photos'] = $this->updatePhotoPaths($validated['photos'], 'component_photos', $component);
        }

        $component->update($validated);
        return $component->refresh();
    }

    public function delete(Component $component): JsonResponse
    {
        if ($component->photos) {
            foreach (json_decode($component->photos) as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }
        $component->delete();
        return response()->json('', 200);
    }
}
