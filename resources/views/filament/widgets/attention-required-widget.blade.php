<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-2 mb-4">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-500" />
            <h3 class="text-lg font-bold">Perlu Perhatian</h3>
        </div>
        
        @if($hasAttention)
            <div class="space-y-3">
                @if($unassessedSuppliersCount > 0)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-warning-100 dark:bg-warning-900/50 rounded-full text-warning-600 dark:text-warning-400">
                                <x-heroicon-m-exclamation-circle class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-sm font-medium">Supplier Belum Dinilai</p>
                                <p class="text-xs text-gray-500">{{ $unassessedSuppliersCount }} supplier tidak memiliki data penilaian kinerja.</p>
                            </div>
                        </div>
                        @if(auth()->user()->role == 'admin')
                            <x-filament::button tag="a" href="{{ url('/admin/supplier-performance-assessments') }}" size="sm" color="gray">
                                Cek
                            </x-filament::button>
                        @endif
                    </div>
                @endif
                
                @if($draftCalculationsCount > 0)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-danger-100 dark:bg-danger-900/50 rounded-full text-danger-600 dark:text-danger-400">
                                <x-heroicon-m-document-text class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-sm font-medium">Perhitungan Belum Selesai</p>
                                <p class="text-xs text-gray-500">Ada {{ $draftCalculationsCount }} perhitungan dengan status Draft.</p>
                            </div>
                        </div>
                        @if(auth()->user()->role == 'admin')
                            <x-filament::button tag="a" href="{{ url('/admin/calculations') }}" size="sm" color="gray">
                                Cek
                            </x-filament::button>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-success-100 dark:bg-success-900/50 text-success-600 dark:text-success-400 mb-3">
                    <x-heroicon-m-check-circle class="w-6 h-6" />
                </div>
                <p class="text-sm text-gray-500">Tidak ada data yang memerlukan perhatian saat ini.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
