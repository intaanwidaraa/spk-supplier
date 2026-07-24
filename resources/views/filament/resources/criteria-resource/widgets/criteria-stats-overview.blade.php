<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

        {{-- Kartu 1: Total Kriteria --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10">
                <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-9 w-9 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kriteria</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalKriteria }}</p>
                <p class="mt-1 text-sm text-primary-600 dark:text-primary-400">Kriteria evaluasi supplier</p>
            </div>
        </div>

        {{-- Kartu 2: Kriteria Benefit --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
                <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-9 w-9 text-success-600 dark:text-success-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kriteria Benefit</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalBenefit }}</p>
                <p class="mt-1 text-sm text-success-600 dark:text-success-400">Nilai lebih tinggi lebih baik</p>
            </div>
        </div>

        {{-- Kartu 3: Kriteria Cost --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-danger-50 dark:bg-danger-500/10">
                <x-filament::icon icon="heroicon-o-arrow-trending-down" class="h-9 w-9 text-danger-600 dark:text-danger-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kriteria Cost</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalCost }}</p>
                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">Nilai mentah lebih rendah lebih baik</p>
            </div>
        </div>

        {{-- Kartu 4: Parameter Penilaian --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-info-50 dark:bg-info-500/10">
                <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-9 w-9 text-info-600 dark:text-info-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Parameter Penilaian</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalParameter }}</p>
                <p class="mt-1 text-sm text-info-600 dark:text-info-400">Panduan skor 1 sampai 5</p>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
