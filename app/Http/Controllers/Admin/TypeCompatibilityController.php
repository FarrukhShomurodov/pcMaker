<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComponentType;
use App\Models\TypeCompatibility;
use Illuminate\Http\Request;

class TypeCompatibilityController extends Controller
{
    public function index()
    {
        $compatibilities = TypeCompatibility::with('componentType', 'compatibleType')->get();
        return view('components.compatibility.index', compact('compatibilities'));
    }

    public function create()
    {
        $types = ComponentType::all();
        return view('components.compatibility.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'component_type_id' => 'required|exists:component_types,id',
            'compatible_with_id' => 'required|array',
            'compatible_with_id.*' => 'exists:component_types,id',
        ]);

        foreach ($data['compatible_with_id'] as $compatibleTypeId) {
            TypeCompatibility::create([
                'component_type_id' => $data['component_type_id'],
                'compatible_type_id' => $compatibleTypeId,
            ]);
        }

        return redirect()->route('component.compatibility.index')->with('success', 'Совместимость добавлена');
    }

    public function edit(TypeCompatibility $typeCompatibility)
    {
        $types = ComponentType::all();
        return view('components.compatibility.edit', compact('typeCompatibility', 'types'));
    }

    public function update(Request $request, TypeCompatibility $typeCompatibility)
    {
        $data = $request->validate([
            'component_type_id' => 'required|exists:component_types,id',
            'compatible_with_id' => 'required|array',
            'compatible_with_id.*' => 'exists:component_types,id',
        ]);


        foreach ($data['compatible_with_id'] as $compatibleTypeId) {
            TypeCompatibility::create([
                'component_type_id' => $data['component_type_id'],
                'compatible_type_id' => $compatibleTypeId,
            ]);
        }

        return redirect()->route('component.compatibility.index')->with('success', 'Совместимость обновлена');
    }

    public function destroy(TypeCompatibility $typeCompatibility)
    {
        $typeCompatibility->delete();

        return redirect()->back()->with('success', 'Совместимость удалена');
    }
}
