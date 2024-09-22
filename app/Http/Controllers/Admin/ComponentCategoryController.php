<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentCategoryRequest;
use App\Models\ComponentCategory;
use App\Services\ComponentCategoryService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ComponentCategoryController extends Controller
{
    protected ComponentCategoryService $componentCategoryService;

    public function __construct(ComponentCategoryService $componentCategoryService)
    {
        $this->componentCategoryService = $componentCategoryService;
    }

    public function index(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        $componentCategories = ComponentCategory::query()->get();

        return view('components.category.index', compact('componentCategories'));
    }

    public function create(): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('components.category.create');
    }

    public function store(ComponentCategoryRequest $request): RedirectResponse
    {
        $this->componentCategoryService->store($request->validated());
        return redirect()->route('component.category.index');
    }

    public function edit(ComponentCategory $componentCategory): View|\Illuminate\Foundation\Application|Factory|Application
    {
        return view('components.category.edit', compact('componentCategory'));
    }

    public function update(ComponentCategory $componentCategory, ComponentCategoryRequest $request): RedirectResponse
    {
        $this->componentCategoryService->update($request->validated(), $componentCategory);
        return redirect()->route('component.category.index');
    }

    public function destroy(ComponentCategory $componentCategory): RedirectResponse
    {
        $this->componentCategoryService->delete($componentCategory);
        return redirect()->route('component.category.index');
    }
}
