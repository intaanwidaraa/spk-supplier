<div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
            <x-filament::button wire:click="tutupDetail" color="gray" icon="heroicon-m-arrow-left">
                Kembali
            </x-filament::button>
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Detail Riwayat Perhitungan</h2>
        </div>
        <div class="flex space-x-2">
            <x-filament::button color="gray" icon="heroicon-m-printer">
                Cetak Laporan
            </x-filament::button>
            <x-filament::button wire:click="gunakanFilterIniLagi" color="primary" icon="heroicon-m-arrow-path">
                Gunakan Filter Ini Lagi
            </x-filament::button>
        </div>
    </div>

    {{-- A. Identitas Perhitungan --}}
    <x-filament::section>
        <x-slot name="heading">Identitas Perhitungan</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-sm">
            <div>
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Kode Perhitungan</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $viewingCalculation->calculation_code }}</p>
            </div>
            <div class="col-span-2 md:col-span-1">
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Nama Perhitungan</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $viewingCalculation->calculation_name }}</p>
            </div>
            <div>
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Waktu Dihitung</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $viewingCalculation->calculated_at ? $viewingCalculation->calculated_at->format('d M Y, H:i') : '-' }}</p>
            </div>
            <div>
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Kategori Supplier</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $viewingCalculation->supplier_category }}</p>
            </div>
            <div>
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Produk / Kelompok</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white">
                    @if($viewingCalculation->product_id)
                        {{ $viewingCalculation->product->nama_produk ?? '-' }}
                    @elseif($viewingCalculation->product_group_id)
                        {{ $viewingCalculation->productGroup->nama_kelompok_produk ?? '-' }}
                    @else
                        Semua Produk
                    @endif
                </p>
            </div>
            <div>
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Periode Filter</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white capitalize">
                    {{ $viewingCalculation->period_type }} <br>
                    <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($viewingCalculation->period_start)->format('d M Y') }} - {{ \Carbon\Carbon::parse($viewingCalculation->period_end)->format('d M Y') }}</span>
                </p>
            </div>
            <div>
                <p class="font-semibold text-gray-500 uppercase tracking-wider text-xs">Total Supplier</p>
                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $viewingCalculation->total_selected }} dari {{ $viewingCalculation->total_candidates }} Kandidat</p>
            </div>
        </div>
    </x-filament::section>

    {{-- B. Supplier yang Digunakan --}}
    <x-filament::section>
        <x-slot name="heading">Supplier yang Digunakan (Data Snapshot)</x-slot>
        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 mt-4">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 w-10 text-center">No</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center" title="Kualitas Produk">C1</th>
                        <th class="px-4 py-3 text-center" title="Harga">C2</th>
                        <th class="px-4 py-3 text-center" title="Masa Kerja Sama">C3</th>
                        <th class="px-4 py-3 text-center" title="Kapasitas Pemenuhan">C4</th>
                        <th class="px-4 py-3 text-center" title="Pengiriman">C5</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($viewingCalculation->selectedSuppliers as $idx => $cs)
                        <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $cs->supplier_name }}</div>
                                <div class="text-xs text-gray-500">{{ $cs->supplier_code }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                    {{ $cs->supplier_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold">{{ $cs->c1_score ?? '-' }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $cs->c2_score ?? '-' }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $cs->c3_score ?? '-' }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $cs->c4_score ?? '-' }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $cs->c5_score ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    wire:click="mountAction('detailRiwayatSupplier', { calculation_supplier_id: {{ $cs->id }}, supplier_name: '{{ $cs->supplier_name }}' })"
                                    class="text-sm text-primary-600 hover:text-primary-500 font-medium transition-colors"
                                >
                                    Lihat Data Pembentuk
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- C. Hasil Pembobotan RECA --}}
    <x-filament::section>
        <x-slot name="heading">Hasil Pembobotan Kriteria (RECA)</x-slot>
        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 mt-4">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">Rank</th>
                        <th class="px-4 py-3">Kriteria</th>
                        <th class="px-4 py-3 text-center">Atribut</th>
                        <th class="px-4 py-3 text-right">Std. Val</th>
                        <th class="px-4 py-3 text-right">Var. Val</th>
                        <th class="px-4 py-3 text-right">Dev. Val</th>
                        <th class="px-4 py-3 text-right bg-primary-50 dark:bg-primary-900/10">Bobot (W)</th>
                        <th class="px-4 py-3 text-right">Persentase</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($viewingCalculation->recaDetails as $detail)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-3 text-center font-bold text-gray-500">{{ $detail->contribution_rank }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                {{ $detail->criteria_code }} - {{ $detail->criteria_name }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $detail->attribute == 'BENEFIT' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20' }}">
                                    {{ $detail->attribute }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-gray-500">{{ number_format($detail->standard_value, 4) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-gray-500">{{ number_format($detail->variation_value, 4) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-gray-500">{{ number_format($detail->deviation_value, 4) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-primary-700 bg-primary-50 dark:bg-primary-900/10 dark:text-primary-400">
                                {{ number_format($detail->weight, 4) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($detail->weight_percentage, 2) }}%
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    wire:click="mountAction('detailKriteria', { criteria_code: '{{ $detail->criteria_code }}' })"
                                    class="text-sm text-primary-600 hover:text-primary-500 font-medium transition-colors"
                                >
                                    Lihat Detail & Perhitungan
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- D. Hasil Ranking MAUT --}}
    <x-filament::section>
        <x-slot name="heading">Hasil Perangkingan Supplier (MAUT)</x-slot>
        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 mt-4">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-center w-16">Peringkat</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3 text-center">Skor Normalisasi (N)</th>
                        <th class="px-4 py-3 text-center">Skor Terbobot (W)</th>
                        <th class="px-4 py-3 text-right">Utilitas Akhir</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($viewingCalculation->mautRankings as $ranking)
                        @php 
                            $norm = is_string($ranking->normalized_scores) ? json_decode($ranking->normalized_scores, true) : $ranking->normalized_scores; 
                            $wgt = is_string($ranking->weighted_scores) ? json_decode($ranking->weighted_scores, true) : $ranking->weighted_scores; 
                        @endphp
                        <tr class="{{ $ranking->rank == 1 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-white dark:bg-gray-900' }}">
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-bold {{ $ranking->rank == 1 ? 'bg-green-500 text-white shadow-sm' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $ranking->rank }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $ranking->supplier_name }}</div>
                                <div class="text-xs text-gray-500">{{ $viewingCalculation->selectedSuppliers->where('supplier_id', $ranking->supplier_id)->first()->supplier_code ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                @if(is_array($norm))
                                    @foreach($norm as $c => $v)
                                        <span class="inline-block mr-1 bg-gray-100 rounded px-1">{{ $c }}: {{ number_format($v, 2) }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                @if(is_array($wgt))
                                    @foreach($wgt as $c => $v)
                                        <span class="inline-block mr-1 bg-gray-100 rounded px-1">{{ $c }}: {{ number_format($v, 2) }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 font-mono text-base">
                                {{ number_format($ranking->final_score, 4) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($ranking->rank == 1)
                                    <span class="inline-flex items-center rounded-md bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                                        Rekomendasi
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</div>
