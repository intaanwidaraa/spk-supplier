<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

        {{-- Total Supplier --}}
        <div
            class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            <div
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10"
            >
                <x-filament::icon
                    icon="heroicon-o-building-office-2"
                    class="h-9 w-9 text-primary-600 dark:text-primary-400"
                />
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Supplier
                </p>

                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">
                    {{ $totalSupplier }}
                </p>

                <p class="mt-1 text-sm text-primary-600 dark:text-primary-400">
                    Seluruh supplier yang terdaftar
                </p>
            </div>
        </div>

        {{-- Raw Material --}}
        <div
            class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            <div
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-warning-50 dark:bg-warning-500/10"
            >
                <x-filament::icon
                    icon="heroicon-o-cube"
                    class="h-9 w-9 text-warning-600 dark:text-warning-400"
                />
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Supplier Raw Material
                </p>

                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">
                    {{ $totalRawMaterial }}
                </p>

                <p class="mt-1 text-sm text-warning-600 dark:text-warning-400">
                    Supplier bahan baku
                </p>
            </div>
        </div>

        {{-- Packaging Material --}}
        <div
            class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            <div
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10"
            >
                <x-filament::icon
                    icon="heroicon-o-archive-box"
                    class="h-9 w-9 text-success-600 dark:text-success-400"
                />
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Supplier Packaging Material
                </p>

                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">
                    {{ $totalPackagingMaterial }}
                </p>

                <p class="mt-1 text-sm text-success-600 dark:text-success-400">
                    Supplier bahan kemasan
                </p>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>