<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

        {{-- Kartu 1: Total Kelompok Produk --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10">
                <x-filament::icon icon="heroicon-o-squares-2x2" class="h-9 w-9 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kelompok Produk</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalKelompok }}</p>
                <p class="mt-1 text-sm text-primary-600 dark:text-primary-400">Kelompok produk yang terdaftar</p>
            </div>
        </div>

        {{-- Kartu 2: Total Detail Produk --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-info-50 dark:bg-info-500/10">
                <x-filament::icon icon="heroicon-o-archive-box" class="h-9 w-9 text-info-600 dark:text-info-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Detail Produk</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalDetail }}</p>
                <p class="mt-1 text-sm text-info-600 dark:text-info-400">Produk detail dari seluruh kelompok</p>
            </div>
        </div>

        {{-- Kartu 3: Packaging Material --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
                <x-filament::icon icon="heroicon-o-archive-box" class="h-9 w-9 text-success-600 dark:text-success-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Packaging Material</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalPackagingKelompok }} <span class="text-lg font-medium text-gray-500">kelompok</span></p>
                <p class="mt-1 text-sm text-success-600 dark:text-success-400">{{ $totalPackagingDetail }} detail produk</p>
            </div>
        </div>

        {{-- Kartu 4: Raw Material --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-500/10">
                <x-filament::icon icon="heroicon-o-beaker" class="h-9 w-9 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Raw Material</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalRawKelompok }} <span class="text-lg font-medium text-gray-500">kelompok</span></p>
                <p class="mt-1 text-sm text-indigo-600 dark:text-indigo-400">{{ $totalRawDetail }} detail produk</p>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
