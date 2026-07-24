<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

        {{-- Kartu 1: Total Transaksi --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10">
                <x-filament::icon icon="heroicon-o-document-text" class="h-9 w-9 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transaksi</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalTransaksi }}</p>
                <p class="mt-1 text-sm text-primary-600 dark:text-primary-400">Riwayat pembelian dan penerimaan</p>
            </div>
        </div>

        {{-- Kartu 2: Supplier Terlibat --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-info-50 dark:bg-info-500/10">
                <x-filament::icon icon="heroicon-o-building-office" class="h-9 w-9 text-info-600 dark:text-info-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier Terlibat</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalSupplier }}</p>
                <p class="mt-1 text-sm text-info-600 dark:text-info-400">Supplier dengan riwayat transaksi</p>
            </div>
        </div>

        {{-- Kartu 3: Total Nilai Pembelian --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
                <x-filament::icon icon="heroicon-o-banknotes" class="h-9 w-9 text-success-600 dark:text-success-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Nilai Pembelian</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalNilai }}</p>
                <p class="mt-1 text-sm text-success-600 dark:text-success-400">Akumulasi nilai transaksi</p>
            </div>
        </div>

        {{-- Kartu 4: Rata-rata Lead Time --}}
        <div class="flex items-center gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-500/10">
                <x-filament::icon icon="heroicon-o-clock" class="h-9 w-9 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rata-rata Lead Time</p>
                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $avgLeadTime }}</p>
                <p class="mt-1 text-sm text-indigo-600 dark:text-indigo-400">Waktu penerimaan rata-rata</p>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
