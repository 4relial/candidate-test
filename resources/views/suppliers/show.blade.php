<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $supplier->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">Supplier</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('suppliers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Back</a>
                <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white">Edit Supplier</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session()->has('supplier_import_review.'.$supplier->id))
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    A pending import needs your review.
                    <a href="{{ route('suppliers.import.review', $supplier) }}" class="font-semibold underline">Continue conflict review</a>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">CLT Layups & CLT Layers</h3>
                            <a href="{{ route('suppliers.layups.create', $supplier) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Add CLT Layup</a>
                        </div>
                    </div>

                    <div class="space-y-4 p-6 text-gray-900 dark:text-gray-100">
                        @forelse ($supplier->layups as $layup)
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-base font-semibold">{{ $layup->name }}</h4>
                                        <p class="text-sm text-gray-500">CLT Layup</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-sm">
                                        <a href="{{ route('suppliers.layups.edit', [$supplier, $layup]) }}" class="text-amber-600">Edit</a>
                                        <a href="{{ route('suppliers.layups.layers.create', [$supplier, $layup]) }}" class="text-indigo-600">Add Layer</a>
                                        <form method="POST" action="{{ route('suppliers.layups.destroy', [$supplier, $layup]) }}" onsubmit="return confirm('Delete this layup?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600">Delete</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-500">
                                                <th class="py-2 pr-3">Order</th>
                                                <th class="py-2 pr-3">Thickness</th>
                                                <th class="py-2 pr-3">Width</th>
                                                <th class="py-2 pr-3">Angle</th>
                                                <th class="py-2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @forelse ($layup->layers as $layer)
                                                <tr>
                                                    <td class="py-2 pr-3">{{ $layer->layer_order }}</td>
                                                    <td class="py-2 pr-3">{{ $layer->thickness }}</td>
                                                    <td class="py-2 pr-3">{{ $layer->width }}</td>
                                                    <td class="py-2 pr-3">{{ $layer->angle }}</td>
                                                    <td class="py-2">
                                                        <div class="flex gap-2 text-sm">
                                                            <a href="{{ route('suppliers.layups.layers.edit', [$supplier, $layup, $layer]) }}" class="text-amber-600">Edit</a>
                                                            <form method="POST" action="{{ route('suppliers.layups.layers.destroy', [$supplier, $layup, $layer]) }}" onsubmit="return confirm('Delete this layer?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-3 text-center text-gray-500">No layers yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No layups yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="text-lg font-semibold">Export</h3>
                            <p class="mt-2 text-sm text-gray-500">Download this supplier with all related layups and layers as JSON.</p>
                            <a href="{{ route('suppliers.export', $supplier) }}" class="mt-4 inline-flex rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">
                                Export JSON
                            </a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                        <form method="POST" action="{{ route('suppliers.import', $supplier) }}" enctype="multipart/form-data" class="space-y-4 p-6 text-gray-900 dark:text-gray-100">
                            @csrf

                            <div>
                                <h3 class="text-lg font-semibold">Import</h3>
                                <p class="mt-2 text-sm text-gray-500">Paste JSON or upload a `.json` file. If conflicts are found, you will review them one by one.</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">Import File (optional)</label>
                                <input type="file" name="import_file" accept=".json,.txt" class="w-full rounded-md border-gray-300">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">JSON Payload</label>
                                <textarea name="payload" rows="12" class="w-full rounded-md border-gray-300 font-mono text-xs">{{ old('payload', json_encode(['layups' => [['name' => 'Sample Layup', 'layers' => [['layer_order' => 1, 'thickness' => 35, 'width' => 120, 'angle' => 0]]]]], JSON_PRETTY_PRINT)) }}</textarea>
                            </div>

                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Analyze Import</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
