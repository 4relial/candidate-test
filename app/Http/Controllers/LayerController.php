<?php

namespace App\Http\Controllers;

use App\Http\Requests\LayerRequest;
use App\Models\Layer;
use App\Models\Layup;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LayerController extends Controller
{
    public function create(Supplier $supplier, Layup $layup): View
    {
        return view('layers.create', compact('supplier', 'layup'));
    }

    public function store(LayerRequest $request, Supplier $supplier, Layup $layup): RedirectResponse
    {
        $layup->layers()->create($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Layer created successfully.');
    }

    public function edit(Supplier $supplier, Layup $layup, Layer $layer): View
    {
        return view('layers.edit', compact('supplier', 'layup', 'layer'));
    }

    public function update(LayerRequest $request, Supplier $supplier, Layup $layup, Layer $layer): RedirectResponse
    {
        $layer->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Layer updated successfully.');
    }

    public function destroy(Supplier $supplier, Layup $layup, Layer $layer): RedirectResponse
    {
        $layer->delete();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Layer deleted successfully.');
    }
}
