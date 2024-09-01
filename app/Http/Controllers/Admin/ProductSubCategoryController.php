<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSubCategoryRequest;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Services\ProductSubCategoryService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductSubCategoryController extends Controller
{
    protected ProductSubCategoryService $productSubCategoryService;

    public function __construct(ProductSubCategoryService $productSubCategoryService)
    {
        $this->productSubCategoryService = $productSubCategoryService;
    }

    public function index(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        $productSubCategories = ProductSubCategory::with('category')->get();
        return view('products.subCategory.index', compact('productSubCategories'));
    }

    public function create(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        $productCategories = ProductCategory::query()->get();
        return view('products.subCategory.create', compact('productCategories'));
    }

    public function store(ProductSubCategoryRequest $request): RedirectResponse
    {
        $this->productSubCategoryService->store($request->validated());
        return redirect()->route('product.sub-category.index');
    }

    public function edit(ProductSubCategory $productSubCategory): View|\Illuminate\Foundation\Application|Factory|Application
    {
        $productCategories = ProductCategory::query()->get();
        return view('products.subCategory.update', compact('productSubCategory', 'productCategories'));
    }

    public function update(ProductSubCategory $productSubCategory, ProductSubCategoryRequest $request): RedirectResponse
    {
        $this->productSubCategoryService->update($request->validated(), $productSubCategory);
        return redirect()->route('product.sub-category.index');
    }

    public function destroy(ProductSubCategory $productSubCategory): RedirectResponse
    {
        $this->productSubCategoryService->delete($productSubCategory);
        return redirect()->route('product.sub-category.index');
    }
}
