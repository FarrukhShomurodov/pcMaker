<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentTypeRequest;
use App\Models\ComponentType;
use App\Services\ComponentTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComponentTypeController extends Controller
{
    protected ComponentTypeService $componentTypeService;

    public function __construct(ComponentTypeService $componentTypeService)
    {
        $this->componentTypeService = $componentTypeService;
    }

    public function index(): View
    {
        $componentTypes = ComponentType::query()->get();

        return view('components.type.index', compact('componentTypes'));
    }

    public function create(): View
    {
        return view('components.type.create');
    }

    public function store(ComponentTypeRequest $request): RedirectResponse
    {
        $this->componentTypeService->store($request->validated());
        return redirect()->route('component-type.index');
    }

    public function edit(ComponentType $componentType): View
    {
        return view('components.type.update', compact('componentType'));
    }

    public function update(ComponentType $componentType, ComponentTypeRequest $request): RedirectResponse
    {
        $this->componentTypeService->update($request->validated(), $componentType);
        return redirect()->route('component-type.index');
    }

    public function destroy(ComponentType $componentType): RedirectResponse
    {
        $this->componentTypeService->delete($componentType);
        return redirect()->route('component-type.index');
    }
}
