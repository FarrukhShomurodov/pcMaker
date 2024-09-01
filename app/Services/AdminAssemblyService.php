<?php

namespace App\Services;

use App\Models\AdminAssembly;
use App\Traits\PhotoTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminAssemblyService
{
    use PhotoTrait;

    public function store(array $validated): Model|Builder
    {
        if (isset($validated['photos'])) {
            $validated['photos'] = $this->storePhotos('admin_assembly_photos');
        }

        return AdminAssembly::query()->create($validated);
    }

    public function update(array $validated, AdminAssembly $adminAssembly): Model|Builder
    {
        if (isset($validated['photos'])) {
            $validated['photos'] = $this->updatePhotoPaths($validated['photos'], 'admin_assembly_photos', $adminAssembly);
        }

        $adminAssembly->update($validated);
        return $adminAssembly->refresh();
    }

    public function delete(AdminAssembly $adminAssembly): JsonResponse
    {
        if ($adminAssembly->photos) {
            foreach (json_decode($adminAssembly->photos) as $photo) {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
        }
        $adminAssembly->delete();
        return response()->json('', 200);
    }
}
