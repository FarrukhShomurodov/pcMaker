<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategoryRequest;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductCategoryController extends Controller
{
    protected ProductCategoryService $productCategoryService;

    public function __construct(ProductCategoryService $productCategoryService)
    {
        $this->productCategoryService = $productCategoryService;
    }

    public function index(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        $productCategories = ProductCategory::query()->get();

        return view('products.category.index', compact('productCategories'));
    }

    public function create(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('products.category.create');
    }

    public function store(ProductCategoryRequest $request): RedirectResponse
    {
        $this->productCategoryService->store($request->validated());
        return redirect()->route('product.category.index');
    }

    public function edit(ProductCategory $productCategory): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('products.category.edit', compact('productCategory'));
    }

    public function update(ProductCategory $productCategory, ProductCategoryRequest $request): RedirectResponse
    {
        $this->productCategoryService->update($request->validated(), $productCategory);
        return redirect()->route('product.category.index');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->productCategoryService->delete($productCategory);
        return redirect()->route('product.category.index');
    }
}
