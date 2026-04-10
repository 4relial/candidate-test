<?php

namespace App\Http\Controllers;

use App\Contracts\SupplierImportExportServiceInterface;
use App\Http\Requests\SupplierImportRequest;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierImportExportServiceInterface $supplierTransferService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $suppliers = Supplier::query()
            ->withCount('layups')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Supplier created successfully.');
    }

    public function show(Request $request, Supplier $supplier): View
    {
        $layupSearch = trim((string) $request->query('layup_search'));

        $supplier->load([
            'layups' => fn ($query) => $query
                ->when($layupSearch !== '', fn ($innerQuery) => $innerQuery->where('name', 'like', "%{$layupSearch}%"))
                ->with('layers')
                ->orderBy('name'),
        ]);

        $layups = $supplier->layups()
            ->when($layupSearch !== '', fn ($query) => $query->where('name', 'like', "%{$layupSearch}%"))
            ->with('layers')
            ->orderBy('name')
            ->paginate(5, ['*'], 'layup_page')
            ->withQueryString();

        return view('suppliers.show', compact('supplier', 'layups', 'layupSearch'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier deleted successfully.');
    }

    public function export(Supplier $supplier): JsonResponse
    {
        return response()
            ->json($this->supplierTransferService->export($supplier))
            ->header('Content-Disposition', 'attachment; filename="supplier-'.$supplier->id.'.json"');
    }

    public function import(SupplierImportRequest $request, Supplier $supplier): RedirectResponse
    {
        $payload = $request->validatedPayload();
        $analysis = $this->supplierTransferService->analyzeImport($supplier, $payload);

        if ($analysis['payload']['layups'] === []) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->withErrors(['payload' => 'No layups found in the provided JSON payload.']);
        }

        $strategy = $request->input('strategy');

        if (is_string($strategy) && $strategy !== '') {
            $decisions = collect($analysis['conflicts'])
                ->mapWithKeys(fn (array $conflict) => [$conflict['id'] => $strategy])
                ->all();

            $summary = $this->supplierTransferService->import($supplier, $analysis['payload'], $decisions, $strategy);

            return $this->redirectWithImportSummary($supplier, $summary);
        }

        if ($analysis['conflicts'] !== []) {
            session()->put($this->reviewSessionKey($supplier), [
                'payload' => $analysis['payload'],
                'conflicts' => $analysis['conflicts'],
                'decisions' => [],
                'current_index' => 0,
            ]);

            return redirect()
                ->route('suppliers.import.review', $supplier)
                ->with('status', 'Conflict detected. Review each layup before finishing the import.');
        }

        $summary = $this->supplierTransferService->import($supplier, $analysis['payload']);

        return $this->redirectWithImportSummary($supplier, $summary);
    }

    public function reviewImport(Supplier $supplier): View|RedirectResponse
    {
        $state = session($this->reviewSessionKey($supplier));

        if (! is_array($state) || ($state['conflicts'] ?? []) === []) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('status', 'No pending import conflicts to review.');
        }

        $currentIndex = (int) ($state['current_index'] ?? 0);
        $conflict = $state['conflicts'][$currentIndex] ?? null;

        if (! is_array($conflict)) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('status', 'No pending import conflicts to review.');
        }

        $totalConflicts = count($state['conflicts']);

        return view('suppliers.import-review', compact('supplier', 'conflict', 'currentIndex', 'totalConflicts'));
    }

    public function resolveImport(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:overwrite,skip,duplicate,reject'],
        ]);

        $state = session($this->reviewSessionKey($supplier));

        if (! is_array($state) || ($state['conflicts'] ?? []) === []) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('status', 'No pending import conflicts to review.');
        }

        if ($validated['action'] === 'reject') {
            session()->forget($this->reviewSessionKey($supplier));

            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('status', 'Import rejected. No changes were applied.');
        }

        $currentIndex = (int) ($state['current_index'] ?? 0);
        $conflict = $state['conflicts'][$currentIndex] ?? null;

        if (! is_array($conflict)) {
            session()->forget($this->reviewSessionKey($supplier));

            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('status', 'Import review session expired.');
        }

        $state['decisions'][$conflict['id']] = $validated['action'];
        $state['current_index'] = $currentIndex + 1;

        if ($state['current_index'] < count($state['conflicts'])) {
            session()->put($this->reviewSessionKey($supplier), $state);

            return redirect()
                ->route('suppliers.import.review', $supplier)
                ->with('status', 'Decision saved. Continue with the next conflict.');
        }

        session()->forget($this->reviewSessionKey($supplier));

        $summary = $this->supplierTransferService->import($supplier, $state['payload'], $state['decisions']);

        return $this->redirectWithImportSummary($supplier, $summary);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function redirectWithImportSummary(Supplier $supplier, array $summary): RedirectResponse
    {
        $status = sprintf(
            'Import completed: %d layup(s) created, %d layup(s) duplicated, %d layup(s) updated, %d layer(s) created, %d layer(s) updated, %d conflict(s) skipped.',
            $summary['created_layups'],
            $summary['duplicated_layups'],
            $summary['updated_layups'],
            $summary['created_layers'],
            $summary['updated_layers'],
            $summary['skipped_conflicts'],
        );

        if (
            $summary['created_layups'] === 0
            && $summary['duplicated_layups'] === 0
            && $summary['updated_layups'] === 0
            && $summary['created_layers'] === 0
            && $summary['updated_layers'] === 0
            && $summary['skipped_conflicts'] === 0
        ) {
            $status = 'Import completed: no changes detected. Existing layups and layers already match the JSON payload.';
        }

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', $status);
    }

    private function reviewSessionKey(Supplier $supplier): string
    {
        return 'supplier_import_review.'.$supplier->id;
    }
}
