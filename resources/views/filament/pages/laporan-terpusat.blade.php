<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="tampilkanLaporan">
            {{ $this->form }}

            @if(!empty($filterData['jenis_laporan']))
            <div class="mt-6 flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass" color="primary">
                    Tampilkan
                </x-filament::button>
                <x-filament::button type="button" wire:click="resetFilter" color="gray" icon="heroicon-m-arrow-path">
                    Reset Filter
                </x-filament::button>
                
                @if($isDataLoaded)
                    <div class="ml-auto flex gap-3 border-l border-gray-300 dark:border-gray-700 pl-4">
                        <x-filament::button type="button" wire:click="exportExcel" color="success" icon="heroicon-m-table-cells">
                            Ekspor Excel
                        </x-filament::button>
                        <x-filament::button type="button" wire:click="cetakLaporan" color="info" icon="heroicon-m-printer">
                            Cetak PDF / Cetak
                        </x-filament::button>
                    </div>
                @endif
            </div>
            @endif
        </form>

        <div wire:loading wire:target="tampilkanLaporan" class="w-full">
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 text-center text-gray-500">
                <x-filament::loading-indicator class="h-8 w-8 inline-block" />
                <p class="mt-2 text-sm">Menyiapkan laporan...</p>
            </div>
        </div>

        @if($isDataLoaded)
            @php $jenis = $filterData['jenis_laporan']; @endphp

            @if(empty($reportData))
                <div class="p-12 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 text-center">
                    <x-heroicon-o-document-magnifying-glass class="h-12 w-12 mx-auto text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">Tidak ada data ditemukan</h3>
                    <p class="mt-1 text-gray-500 text-sm">Cobalah untuk mengubah parameter filter Anda.</p>
                </div>
            @else
                
                {{-- RINGKASAN & PREVIEW TABEL --}}
                @if($jenis == 'evaluasi' || $jenis == 'ranking')
                    @php $calc = $reportData['calculation']; $ranks = $reportData['rankings']; @endphp
                    
                    <x-filament::section>
                        <x-slot name="heading">Ringkasan Hasil Evaluasi MAUT</x-slot>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm">
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Kode Perhitungan</p><p class="font-medium text-gray-900 dark:text-white mt-1">{{ $calc->calculation_code }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Dievaluasi</p><p class="font-medium text-gray-900 dark:text-white mt-1">{{ $calc->total_selected }} Supplier</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Peringkat 1</p><p class="font-bold text-green-600 mt-1">{{ $ranks->first()->supplier_name ?? '-' }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Nilai Tertinggi</p><p class="font-mono text-gray-900 dark:text-white mt-1">{{ number_format($ranks->first()->final_score ?? 0, 4) }}</p></div>
                        </div>
                    </x-filament::section>

                    @if($jenis == 'evaluasi')
                        <h3 class="text-lg font-bold mt-8 mb-2">A. Preview Bobot Kriteria (RECA)</h3>
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    <tr><th class="px-4 py-2">Kode</th><th class="px-4 py-2">Kriteria</th><th class="px-4 py-2 text-right">Bobot</th><th class="px-4 py-2 text-right">Persentase</th><th class="px-4 py-2 text-center">Rank</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($calc->recaDetails as $d)
                                        <tr class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                            <td class="px-4 py-2">{{ $d->criteria_code }}</td><td class="px-4 py-2">{{ $d->criteria_name }}</td><td class="px-4 py-2 text-right">{{ number_format($d->weight, 4) }}</td><td class="px-4 py-2 text-right">{{ number_format($d->weight_percentage, 2) }}%</td><td class="px-4 py-2 text-center">{{ $d->contribution_rank }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <h3 class="text-lg font-bold mt-8 mb-2">B. Preview Ranking (MAUT)</h3>
                    @else
                        <h3 class="text-lg font-bold mt-8 mb-2">Preview Ranking Supplier (MAUT)</h3>
                    @endif

                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2 w-10 text-center">Rank</th>
                                    <th class="px-4 py-2">Kode</th>
                                    <th class="px-4 py-2">Supplier</th>
                                    @if($jenis == 'evaluasi')
                                        <th class="px-4 py-2 text-center">C1</th>
                                        <th class="px-4 py-2 text-center">C2</th>
                                        <th class="px-4 py-2 text-center">C3</th>
                                        <th class="px-4 py-2 text-center">C4</th>
                                        <th class="px-4 py-2 text-center">C5</th>
                                    @endif
                                    <th class="px-4 py-2 text-right">Skor MAUT</th>
                                    <th class="px-4 py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ranks as $r)
                                    @php $supSnap = $calc->selectedSuppliers->where('supplier_id', $r->supplier_id)->first(); @endphp
                                    <tr class="border-t border-gray-200 dark:border-gray-700 {{ $r->rank == 1 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-white dark:bg-gray-900' }}">
                                        <td class="px-4 py-2 text-center font-bold">{{ $r->rank }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $supSnap->supplier_code ?? '-' }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $r->supplier_name }}</td>
                                        @if($jenis == 'evaluasi')
                                            <td class="px-4 py-2 text-center">{{ $supSnap->c1_score ?? '-' }}</td>
                                            <td class="px-4 py-2 text-center">{{ $supSnap->c2_score ?? '-' }}</td>
                                            <td class="px-4 py-2 text-center">{{ $supSnap->c3_score ?? '-' }}</td>
                                            <td class="px-4 py-2 text-center">{{ $supSnap->c4_score ?? '-' }}</td>
                                            <td class="px-4 py-2 text-center">{{ $supSnap->c5_score ?? '-' }}</td>
                                        @endif
                                        <td class="px-4 py-2 text-right font-mono">{{ number_format($r->final_score, 4) }}</td>
                                        <td class="px-4 py-2 text-center">
                                            @if($r->rank == 1) <span class="text-green-700 font-medium">Rekomendasi</span> @else - @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @elseif($jenis == 'pembobotan')
                    @php $calc = $reportData['calculation']; @endphp
                    <x-filament::section>
                        <x-slot name="heading">Ringkasan Pembobotan RECA</x-slot>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm">
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Kode Perhitungan</p><p class="font-medium mt-1">{{ $calc->calculation_code }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Jumlah Kriteria</p><p class="font-medium mt-1">{{ $calc->recaDetails->count() }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Bobot Tertinggi</p><p class="font-bold text-blue-600 mt-1">{{ $calc->recaDetails->first()->criteria_code ?? '-' }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Persentase</p><p class="font-mono mt-1">{{ number_format($calc->recaDetails->sum('weight_percentage'), 0) }}%</p></div>
                        </div>
                    </x-filament::section>
                    <h3 class="text-lg font-bold mt-8 mb-2">Preview Tabel Bobot RECA</h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2">Kode</th><th class="px-4 py-2">Kriteria</th><th class="px-4 py-2">Atribut</th>
                                    <th class="px-4 py-2 text-right">Std Val</th><th class="px-4 py-2 text-right">Var Val</th><th class="px-4 py-2 text-right">Dev Val</th>
                                    <th class="px-4 py-2 text-right">Bobot</th><th class="px-4 py-2 text-right">Persentase</th><th class="px-4 py-2 text-center">Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calc->recaDetails as $d)
                                    <tr class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                        <td class="px-4 py-2">{{ $d->criteria_code }}</td><td class="px-4 py-2">{{ $d->criteria_name }}</td><td class="px-4 py-2">{{ $d->attribute }}</td>
                                        <td class="px-4 py-2 text-right text-gray-500 font-mono">{{ number_format($d->standard_value, 4) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-500 font-mono">{{ number_format($d->variation_value, 4) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-500 font-mono">{{ number_format($d->deviation_value, 4) }}</td>
                                        <td class="px-4 py-2 text-right font-bold">{{ number_format($d->weight, 4) }}</td><td class="px-4 py-2 text-right">{{ number_format($d->weight_percentage, 2) }}%</td><td class="px-4 py-2 text-center">{{ $d->contribution_rank }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @elseif($jenis == 'penilaian')
                    @php $data = collect($reportData['penilaian']); @endphp
                    <x-filament::section>
                        <x-slot name="heading">Ringkasan Penilaian Kinerja</x-slot>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm">
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Data Supplier</p><p class="font-medium mt-1">{{ $data->count() }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Data Lengkap</p><p class="font-medium mt-1 text-green-600">{{ $data->where('status_data', 'Lengkap')->count() }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Kategori</p><p class="font-medium mt-1">{{ collect($this->form->getComponent('kategori')->getOptions())->get($filterData['kategori'] ?? '') ?? '-' }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Periode</p><p class="font-medium mt-1">{{ \Carbon\Carbon::parse($filterData['period_start'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($filterData['period_end'])->format('d/m/Y') }}</p></div>
                        </div>
                    </x-filament::section>
                    <h3 class="text-lg font-bold mt-8 mb-2">Preview Penilaian Kinerja Supplier</h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2">Kode</th><th class="px-4 py-2">Supplier</th><th class="px-4 py-2 text-center">C1</th><th class="px-4 py-2 text-center">C2</th><th class="px-4 py-2 text-center">C3</th><th class="px-4 py-2 text-center">C4</th><th class="px-4 py-2 text-center">C5</th><th class="px-4 py-2 text-center">Total</th><th class="px-4 py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $r)
                                    <tr class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                        <td class="px-4 py-2">{{ $r['supplier_code'] }}</td><td class="px-4 py-2">{{ $r['supplier_name'] }}</td>
                                        <td class="px-4 py-2 text-center">{{ $r['c1'] ?: '-' }}</td><td class="px-4 py-2 text-center">{{ $r['c2'] ?: '-' }}</td><td class="px-4 py-2 text-center">{{ $r['c3'] ?: '-' }}</td><td class="px-4 py-2 text-center">{{ $r['c4'] ?: '-' }}</td><td class="px-4 py-2 text-center">{{ $r['c5'] ?: '-' }}</td>
                                        <td class="px-4 py-2 text-center font-bold">{{ $r['total'] }}</td>
                                        <td class="px-4 py-2 text-center">
                                            @if($r['status_data'] == 'Lengkap') <span class="text-green-600">Lengkap</span> @else <span class="text-orange-500">Tdk Lengkap</span> @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @elseif($jenis == 'historis')
                    @php $data = collect($reportData['historis']); @endphp
                    <x-filament::section>
                        <x-slot name="heading">Ringkasan Pembelian</x-slot>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm">
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Transaksi</p><p class="font-medium mt-1">{{ $data->count() }} PO</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Qty Dipesan</p><p class="font-medium mt-1">{{ number_format($data->sum('qty_pembelian'), 0) }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Qty Diterima</p><p class="font-medium mt-1">{{ number_format($data->sum('qty_diterima'), 0) }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Rata-rata Lead Time</p><p class="font-medium mt-1">{{ number_format($data->avg('lead_time_hari'), 1) }} Hari</p></div>
                        </div>
                    </x-filament::section>
                    <h3 class="text-lg font-bold mt-8 mb-2">Preview Transaksi Historis</h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2">PO Date</th><th class="px-3 py-2">Receive Date</th><th class="px-3 py-2">Supplier</th><th class="px-3 py-2">Produk</th>
                                    <th class="px-3 py-2 text-right">Qty PO</th><th class="px-3 py-2 text-right">Qty Rcv</th><th class="px-3 py-2 text-right">Harga Sat.</th><th class="px-3 py-2 text-right">Lead Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data->take(50) as $r)
                                    <tr class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($r->tanggal_pembelian)->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">{{ $r->tanggal_penerimaan ? \Carbon\Carbon::parse($r->tanggal_penerimaan)->format('d/m/Y') : '-' }}</td>
                                        <td class="px-3 py-2">{{ $r->supplier->nama_supplier ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $r->nama_produk }}</td>
                                        <td class="px-3 py-2 text-right">{{ $r->qty_pembelian }}</td>
                                        <td class="px-3 py-2 text-right">{{ $r->qty_diterima }}</td>
                                        <td class="px-3 py-2 text-right">Rp {{ number_format($r->harga_satuan, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right">{{ $r->lead_time_hari ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($data->count() > 50) <div class="p-2 text-center text-xs text-gray-500 bg-gray-50">Menampilkan 50 data terbaru dari total {{ $data->count() }} (Ekspor untuk melihat seluruh data)</div> @endif
                    </div>

                @elseif($jenis == 'riwayat')
                    @php $data = collect($reportData['riwayat']); @endphp
                    <x-filament::section>
                        <x-slot name="heading">Ringkasan Riwayat</x-slot>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm">
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Total Perhitungan</p><p class="font-medium mt-1">{{ $data->count() }}</p></div>
                            <div><p class="text-gray-500 text-xs uppercase font-bold">Status Final</p><p class="font-medium mt-1 text-green-600">{{ $data->where('status', 'Final')->count() }}</p></div>
                        </div>
                    </x-filament::section>
                    <h3 class="text-lg font-bold mt-8 mb-2">Preview Riwayat</h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2">Kode</th><th class="px-4 py-2">Nama</th><th class="px-4 py-2">Tanggal Proses</th><th class="px-4 py-2 text-center">Supplier</th><th class="px-4 py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $r)
                                    <tr class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                        <td class="px-4 py-2">{{ $r->calculation_code }}</td><td class="px-4 py-2">{{ $r->calculation_name }}</td>
                                        <td class="px-4 py-2">{{ $r->calculated_at ? $r->calculated_at->format('d M Y, H:i') : $r->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-2 text-center">{{ $r->total_selected }}</td>
                                        <td class="px-4 py-2 text-center">{{ $r->status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @elseif($jenis == 'supplier')
                    @php $data = collect($reportData['supplier']); @endphp
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-8">
                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 flex items-center gap-4">
                            <div class="p-3 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-lg">
                                <x-heroicon-o-building-office-2 class="w-8 h-8" />
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Supplier</h4>
                                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $data->count() }}</div>
                                <p class="text-xs text-gray-500 mt-1">Seluruh supplier yang terdaftar</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 flex items-center gap-4">
                            <div class="p-3 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-lg">
                                <x-heroicon-o-cube class="w-8 h-8" />
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier Raw Material</h4>
                                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $data->where('kategori', 'Raw Material')->count() }}</div>
                                <p class="text-xs text-gray-500 mt-1">Supplier bahan baku</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 flex items-center gap-4">
                            <div class="p-3 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-lg">
                                <x-heroicon-o-archive-box class="w-8 h-8" />
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier Packaging Material</h4>
                                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $data->where('kategori', 'Packaging Material')->count() }}</div>
                                <p class="text-xs text-gray-500 mt-1">Supplier bahan kemasan</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold mb-2 text-gray-900 dark:text-white">Daftar Supplier</h3>
                    <p class="text-sm text-gray-500 mb-6">Daftar supplier berdasarkan filter laporan yang dipilih.</p>
                    
                    <div class="overflow-x-auto w-full">
                        {{ $this->table }}
                    </div>
                @endif
                
            @endif
        @endif
    </div>

    {{-- Browser print dispatch listener --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-print-window', (url) => {
                window.open(url[0]?.url || url.url || url, '_blank', 'width=1000,height=800');
            });
        });
    </script>
</x-filament-panels::page>