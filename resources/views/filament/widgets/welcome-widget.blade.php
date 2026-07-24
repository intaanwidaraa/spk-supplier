<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <h2 class="text-2xl font-bold tracking-tight">
                    Selamat Datang, {{ auth()->user()->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Pantau data supplier, hasil penilaian, dan riwayat perhitungan melalui dashboard ini.
                </p>
                <div class="mt-4 flex gap-3 text-sm">
                    <div class="px-3 py-1 bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 rounded-full font-medium">
                        Role: {{ auth()->user()->role == 'admin' ? 'SPV Purchasing / Admin' : 'Direktur' }}
                    </div>
                    <div class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-full font-medium">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </div>
                </div>
                
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button tag="a" href="{{ url('/admin/calculation-histories') }}" color="primary">
                        Riwayat Perhitungan
                    </x-filament::button>
                    
                    <x-filament::button tag="a" href="{{ url('/admin/laporan-terpusat') }}" color="gray">
                        Laporan Terpusat
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
