<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Create Supplier
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-4 p-6 text-gray-900 dark:text-gray-100">
                    @csrf

                    <div>
                        <label class="mb-1 block text-sm font-medium">Supplier Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300" required>
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('suppliers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancel</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
