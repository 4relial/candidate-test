<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Conflict Review
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Layup {{ $currentIndex + 1 }} of {{ $totalConflicts }}
                </p>
            </div>

            <a href="{{ route('suppliers.show', $supplier) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm">
                Back to Supplier
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $conflict['message'] }}
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Existing Version</h3>
                        <p class="text-sm text-gray-500">{{ $conflict['existing']['name'] }}</p>
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-3">Order</th>
                                        <th class="py-2 pr-3">Thickness</th>
                                        <th class="py-2 pr-3">Width</th>
                                        <th class="py-2">Angle</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($conflict['existing']['layers'] as $layer)
                                        <tr>
                                            <td class="py-2 pr-3">{{ $layer['layer_order'] }}</td>
                                            <td class="py-2 pr-3">{{ $layer['thickness'] }}</td>
                                            <td class="py-2 pr-3">{{ $layer['width'] }}</td>
                                            <td class="py-2">{{ $layer['angle'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-3 text-center text-gray-500">No existing layers.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Incoming Version</h3>
                        <p class="text-sm text-gray-500">{{ $conflict['incoming']['name'] }}</p>
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-3">Order</th>
                                        <th class="py-2 pr-3">Thickness</th>
                                        <th class="py-2 pr-3">Width</th>
                                        <th class="py-2">Angle</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($conflict['incoming']['layers'] as $layer)
                                        <tr>
                                            <td class="py-2 pr-3">{{ $layer['layer_order'] }}</td>
                                            <td class="py-2 pr-3">{{ $layer['thickness'] }}</td>
                                            <td class="py-2 pr-3">{{ $layer['width'] }}</td>
                                            <td class="py-2">{{ $layer['angle'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-3 text-center text-gray-500">No incoming layers.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if ($conflict['layer_conflicts'] !== [])
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Detected Differences</h3>
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="space-y-3">
                            @foreach ($conflict['layer_conflicts'] as $layerConflict)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <p class="font-semibold">Layer {{ $layerConflict['layer_order'] }}</p>
                                    <p class="mt-1 text-gray-600">
                                        Existing: T {{ $layerConflict['existing']['thickness'] }}, W {{ $layerConflict['existing']['width'] }}, A {{ $layerConflict['existing']['angle'] }}
                                    </p>
                                    <p class="text-gray-600">
                                        Incoming: T {{ $layerConflict['incoming']['thickness'] }}, W {{ $layerConflict['incoming']['width'] }}, A {{ $layerConflict['incoming']['angle'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold">Choose Action</h3>
                    <p class="mt-2 text-sm text-gray-500">Resolve this layup first, then continue to the next conflict if any remain.</p>

                    <form method="POST" action="{{ route('suppliers.import.resolve', $supplier) }}" class="mt-4 flex flex-wrap gap-3">
                        @csrf
                        <button type="submit" name="action" value="overwrite" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                            Overwrite Existing
                        </button>
                        <button type="submit" name="action" value="skip" class="rounded-md bg-slate-600 px-4 py-2 text-sm font-semibold text-white">
                            Skip This Layup
                        </button>
                        <button type="submit" name="action" value="duplicate" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white">
                            Duplicate as {{ $conflict['incoming']['name'] }} (imported)
                        </button>
                        <button type="submit" name="action" value="reject" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">
                            Reject Entire Import
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
