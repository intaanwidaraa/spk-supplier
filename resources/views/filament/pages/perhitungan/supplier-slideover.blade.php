<div>
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $supplier->nama_supplier }} ({{ $supplier->kode_supplier }})</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Status: {{ $supplier->status_kerja_sama }} | Masa Kerja Sama: {{ $supplier->masa_kerja_sama }} Tahun</p>
    </div>

    <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Nilai Kriteria & Data Agregasi (C1-C5)</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @foreach(['C1' => 'Kualitas Produk', 'C2' => 'Harga', 'C3' => 'Masa Kerja Sama', 'C4' => 'Kapasitas Pemenuhan', 'C5' => 'Pengiriman'] as $kode => $nama)
            @php
                $score = $candidate['scores_data'][$kode]['score'] ?? null;
                $data = $candidate['scores_data'][$kode]['data'] ?? [];
                
                $badgeColor = match((int)$score) {
                    5 => 'bg-green-500', 4 => 'bg-blue-500',
                    3 => 'bg-gray-400', 2 => 'bg-yellow-500', 1 => 'bg-red-500',
                    default => 'bg-gray-300',
                };
            @endphp
            <div class="rounded-xl border border-gray-200 shadow-sm p-4 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-gray-900 dark:text-white">{{ $kode }} - {{ $nama }}</span>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $badgeColor }} text-sm font-bold text-white shadow">
                        {{ $score ?? '-' }}
                    </span>
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    @foreach($data as $key => $val)
                        @if($key !== 'label')
                            <div class="flex justify-between">
                                <span class="capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                <span class="font-mono">{{ is_numeric($val) && strpos($val, '.') !== false ? number_format($val, 2) : $val }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-3 text-xs font-semibold text-primary-600 dark:text-primary-400">
                    {{ $data['label'] ?? '' }}
                </div>
            </div>
        @endforeach
    </div>

    <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Data Transaksi (Berdasarkan Filter)</h4>
    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
        <table class="w-full text-xs text-left">
            <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-3 py-2">No. PO</th>
                    <th class="px-3 py-2">Produk</th>
                    <th class="px-3 py-2">Tgl Beli</th>
                    <th class="px-3 py-2">Tgl Terima</th>
                    <th class="px-3 py-2 text-right">Lead Time (Hr)</th>
                    <th class="px-3 py-2 text-right">Qty Beli</th>
                    <th class="px-3 py-2 text-right">Qty Terima</th>
                    <th class="px-3 py-2 text-right">Harga</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($transactions as $t)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-3 py-2 font-medium">{{ $t->nomor_po ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $t->nama_produk ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $t->tanggal_pembelian ? \Carbon\Carbon::parse($t->tanggal_pembelian)->format('d/m/Y') : '-' }}</td>
                        <td class="px-3 py-2">{{ $t->tanggal_penerimaan ? \Carbon\Carbon::parse($t->tanggal_penerimaan)->format('d/m/Y') : '-' }}</td>
                        <td class="px-3 py-2 text-right">{{ $t->lead_time_hari ?? '-' }}</td>
                        <td class="px-3 py-2 text-right">{{ $t->qty_pembelian ?? '-' }}</td>
                        <td class="px-3 py-2 text-right">{{ $t->qty_diterima ?? '-' }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($t->harga_satuan ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-4 text-center text-gray-500">Tidak ada transaksi yang cocok dengan filter produk dan periode.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
