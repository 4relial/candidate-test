<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Suppliers
            </h2>
            @can('manage-suppliers')
                <a href="{{ route('suppliers.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                    Add Supplier
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @cannot('manage-suppliers')
                <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                    You currently have <span class="font-semibold">view-only access</span>. Supplier data can be viewed and exported, but only allowed users can manage it.
                </div>
            @endcannot

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <form method="GET" action="{{ route('suppliers.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div class="w-full md:max-w-md">
                            <label for="search" class="mb-1 block text-sm font-medium">Search Supplier Name</label>
                            <input id="search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Type supplier name..." class="w-full rounded-md border-gray-300">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Search</button>
                            @if (! empty($search))
                                <a href="{{ route('suppliers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Reset</a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-3 pr-4">Supplier Name</th>
                                    <th class="py-3 pr-4">CLT Layups</th>
                                    <th class="py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($suppliers as $supplier)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium">{{ $supplier->name }}</td>
                                        <td class="py-3 pr-4">{{ $supplier->layups_count }}</td>
                                        <td class="py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600">View</a>
                                                @can('manage-suppliers')
                                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="text-amber-600">Edit</a>
                                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600">Delete</button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-gray-500">No suppliers yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $suppliers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
