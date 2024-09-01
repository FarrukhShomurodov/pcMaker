<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $products = Product::with('category')->with('subCategory')->get();

        return view('products.index', compact('products'));
    }

    public function create(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $productCategories = ProductCategory::all();
        return view('products.create', compact('productCategories'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->productService->store($request->validated());
        return redirect()->route('product.items.index');
    }

    public function edit(Product $product): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $productCategories = ProductCategory::all();
        return view('products.update', compact('product', 'productCategories'));
    }

    public function update(Product $product, ProductRequest $request): RedirectResponse
    {
        $this->productService->update($request->validated(), $product);
        return redirect()->route('product.items.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);
        return redirect()->route('product.items.index');
    }
}
