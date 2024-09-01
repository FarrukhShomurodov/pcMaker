<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait PhotoTrait
{
    protected function storePhotos(string $storagePath): string
    {

        $photos = array_map(function ($file) use ($storagePath) {
            return $file->store($storagePath, 'public');
        }, request()->file('photos'));

        return json_encode($photos);
    }

    public function updatePhotoPaths(array $validated, string $storagePath, $model): bool|string|null
    {
        $photos = $validated;
        $uploadedPhotos = [];

        foreach ($photos as $photo) {
            $path = $photo->store($storagePath, 'public');
            $uploadedPhotos[] = $path;
        }

        $existingPhotos = json_decode($model->photos) ?: [];
        $allPhotos = array_merge($existingPhotos, $uploadedPhotos);
        return json_encode($allPhotos);

    }

    public function delete($model, $photosUrl, string $photoPath, string $storagePath): void
    {
        $updatedPhotosUrl = json_encode(array_values($photosUrl));

        $model->update(['photos' => $updatedPhotosUrl]);

        Storage::disk('public')->delete($storagePath . $photoPath);
    }
}
