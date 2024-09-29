<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryCompatibility;
use App\Models\ComponentCategory;
use Illuminate\Http\Request;

class CategoryCompatibilityController extends Controller
{
    public function index()
    {
        $compatibilities = CategoryCompatibility::with('componentCategory', 'compatibleCategory')->get();
        return view('components.category-compatibility.index', compact('compatibilities'));
    }

    public function create()
    {
        $categories = ComponentCategory::all();
        return view('components.category-compatibility.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'component_category_id' => 'required|exists:component_categories,id',
            'compatible_with_id' => 'required|array',
            'compatible_with_id.*' => 'exists:component_categories,id',
        ]);

        foreach ($data['compatible_with_id'] as $compatibleTypeId) {
            CategoryCompatibility::query()->create([
                'component_category_id' => $data['component_category_id'],
                'compatible_category_id' => $compatibleTypeId,
            ]);
        }

        return redirect()->route('component.category-compatibility.index')->with('success', 'Совместимость добавлена');
    }

    public function edit(CategoryCompatibility $categoryCompatibility)
    {
        $categories = ComponentCategory::all();
        return view('components.category-compatibility.edit', compact('categoryCompatibility', 'categories'));
    }

    public function update(Request $request, CategoryCompatibility $categoryCompatibility)
    {
        $data = $request->validate([
            'component_category_id' => 'required|exists:component_categories,id',
            'compatible_with_id' => 'required|array',
            'compatible_with_id.*' => 'exists:component_categories,id',
        ]);

        foreach ($data['compatible_with_id'] as $compatibleCategoryId) {
            $categoryCompatibility->update([
                'component_category_id' => $data['component_category_id'],
                'compatible_category_id' => $compatibleCategoryId,
            ]);
        }

        return redirect()->route('component.category-compatibility.index')->with('success', 'Совместимость обновлена');
    }

    public function destroy(CategoryCompatibility $categoryCompatibility)
    {
        $categoryCompatibility->delete();

        return redirect()->back()->with('success', 'Совместимость удалена');
    }
}
