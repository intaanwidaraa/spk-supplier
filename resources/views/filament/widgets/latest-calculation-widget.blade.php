<x-filament-widgets::widget>
    <x-filament::section>
        <h3 class="text-lg font-bold mb-4">Perhitungan Terakhir</h3>
        
        @if($calculation)
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Kode Perhitungan</span>
                    <span class="font-medium">{{ $calculation->calculation_code }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Tanggal</span>
                    <span class="font-medium">{{ \Carbon\Carbon::parse($calculation->period_start)->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Kategori Supplier</span>
                    <span class="px-2 py-1 text-xs rounded-md bg-info-100 dark:bg-info-900/50 text-info-700 dark:text-info-400">{{ $calculation->supplier_category }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Kelompok Produk</span>
                    <span class="font-medium">{{ $calculation->product_group_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Jumlah Evaluasi</span>
                    <span class="font-medium">{{ $calculation->total_selected }} Supplier</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Status</span>
                    @php
                        $color = match($calculation->status) {
                            'Final', 'Selesai' => 'success',
                            'Draft' => 'warning',
                            default => 'gray',
                        };
                    @endphp
                    <span class="px-2 py-1 text-xs rounded-md bg-{{$color}}-100 dark:bg-{{$color}}-900/50 text-{{$color}}-700 dark:text-{{$color}}-400">{{ $calculation->status }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Dijalankan Oleh</span>
                    <span class="font-medium">{{ $user ? $user->name : '-' }}</span>
                </div>
                
                @if($calculation->mautRankings->isNotEmpty())
                    <div class="flex justify-between pt-2">
                        <span class="text-gray-500">Peringkat 1</span>
                        <div class="text-right">
                            <span class="font-bold text-primary-600 block">{{ $calculation->mautRankings->first()->supplier_name }}</span>
                            <span class="text-xs text-gray-500">Skor: {{ number_format($calculation->mautRankings->first()->final_score, 4) }}</span>
                        </div>
                    </div>
                @endif
                
                <div class="mt-4 text-center">
                    <x-filament::button tag="a" href="{{ url('/admin/calculation-histories/'.$calculation->id) }}" size="sm" class="w-full" style="justify-content: center">
                        Lihat Riwayat
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="text-center py-6 text-gray-500">
                Belum ada riwayat perhitungan supplier.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
