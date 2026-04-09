<?php

namespace App\Http\Controllers;

use App\Http\Requests\LayupRequest;
use App\Models\Layup;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LayupController extends Controller
{
    public function create(Supplier $supplier): View
    {
        return view('layups.create', compact('supplier'));
    }

    public function store(LayupRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->layups()->create($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Layup created successfully.');
    }

    public function edit(Supplier $supplier, Layup $layup): View
    {
        return view('layups.edit', compact('supplier', 'layup'));
    }

    public function update(LayupRequest $request, Supplier $supplier, Layup $layup): RedirectResponse
    {
        $layup->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Layup updated successfully.');
    }

    public function destroy(Supplier $supplier, Layup $layup): RedirectResponse
    {
        $layup->delete();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Layup deleted successfully.');
    }
}
