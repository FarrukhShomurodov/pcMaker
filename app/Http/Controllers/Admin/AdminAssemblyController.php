<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAssemblyRequest;
use App\Models\AdminAssembly;
use App\Services\AdminAssemblyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAssemblyController extends Controller
{
    protected AdminAssemblyService $adminAssemblyService;

    public function __construct(AdminAssemblyService $adminAssemblyService)
    {
        $this->adminAssemblyService = $adminAssemblyService;
    }

    public function index(): View
    {
        $adminAssemblies = AdminAssembly::all();

        return view('adminAssemblies.index', compact('adminAssemblies'));
    }

    public function create(): View
    {
        return view('adminAssemblies.create');
    }

    public function store(AdminAssemblyRequest $request): RedirectResponse
    {
        $this->adminAssemblyService->store($request->validated());
        return redirect()->route('admin-assembly.index');
    }

    public function edit(AdminAssembly $adminAssembly): View
    {
        return view('adminAssemblies.update', compact('adminAssembly'));
    }

    public function update(AdminAssembly $adminAssembly, AdminAssemblyRequest $request): RedirectResponse
    {
        $this->adminAssemblyService->update($request->validated(), $adminAssembly);
        return redirect()->route('admin-assembly.index');
    }

    public function destroy(AdminAssembly $adminAssembly): RedirectResponse
    {
        $this->adminAssemblyService->delete($adminAssembly);
        return redirect()->route('admin-assembly.index');
    }
}
