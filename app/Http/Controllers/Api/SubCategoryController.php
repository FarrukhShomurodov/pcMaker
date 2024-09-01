<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

class SubCategoryController extends Controller
{
    public function fetchByCategory(ProductCategory $productCategory): JsonResponse
    {
        $subCategories = $productCategory->subCategories()->get();

        return response()->json($subCategories, 200);
    }
}
