<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit Layer
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('suppliers.layups.layers.update', [$supplier, $layup, $layer]) }}" class="space-y-4 p-6 text-gray-900 dark:text-gray-100">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-1 block text-sm font-medium">Layer Order</label>
                        <input type="number" min="1" name="layer_order" value="{{ old('layer_order', $layer->layer_order) }}" class="w-full rounded-md border-gray-300" required>
                        @error('layer_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Thickness</label>
                            <input type="number" step="0.01" min="0" name="thickness" value="{{ old('thickness', $layer->thickness) }}" class="w-full rounded-md border-gray-300" required>
                            @error('thickness')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Width</label>
                            <input type="number" step="0.01" min="0" name="width" value="{{ old('width', $layer->width) }}" class="w-full rounded-md border-gray-300" required>
                            @error('width')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Angle</label>
                            <input type="number" step="0.01" name="angle" value="{{ old('angle', $layer->angle) }}" class="w-full rounded-md border-gray-300" required>
                            @error('angle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancel</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Update Layer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
