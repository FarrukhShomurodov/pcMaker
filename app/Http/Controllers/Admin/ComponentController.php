<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComponentRequest;
use App\Models\Component;
use App\Models\ComponentCategory;
use App\Models\ComponentType;
use App\Services\ComponentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComponentController extends Controller
{
    protected ComponentService $componentService;

    public function __construct(ComponentService $componentService)
    {
        $this->componentService = $componentService;
    }

    public function index(): View
    {
        $components = Component::with('category')->with('type')->get();

        return view('components.index', compact('components'));
    }

    public function create(): View
    {
        $componentCategories = ComponentCategory::all();
        $componentTypes = ComponentType::all();
        return view('components.create', compact('componentCategories', 'componentTypes'));
    }

    public function store(ComponentRequest $request): RedirectResponse
    {
        $this->componentService->store($request->validated());
        return redirect()->route('component.index');
    }

    public function edit(Component $component): View
    {
        $componentCategories = ComponentCategory::all();
        $componentTypes = ComponentType::all();
        return view('components.update', compact('component', 'componentCategories', 'componentTypes'));
    }

    public function update(Component $component, ComponentRequest $request): RedirectResponse
    {
        $this->componentService->update($request->validated(), $component);
        return redirect()->route('component.index');
    }

    public function destroy(Component $component): RedirectResponse
    {
        $this->componentService->delete($component);
        return redirect()->route('component.index');
    }
}
