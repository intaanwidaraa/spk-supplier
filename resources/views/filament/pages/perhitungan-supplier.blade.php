<x-filament-panels::page>
    @if(!$viewingCalculation)
        <div class="space-y-6">
        
        {{-- CARD 1: Ruang Lingkup Perhitungan (Filter) --}}
        <x-filament::section>
            <x-slot name="heading">Ruang Lingkup Perhitungan</x-slot>
            <x-slot name="description">Pilih parameter untuk mengambil data transaksi supplier</x-slot>
            
            <form wire:submit="tampilkanDataSupplier">
                {{ $this->form }}

                <div class="mt-6 flex gap-3">
                    <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                        Tampilkan Data Supplier
                    </x-filament::button>
                    @if($isFilterSubmitted)
                        <x-filament::button type="button" color="gray" wire:click="resetFilter" icon="heroicon-m-arrow-path">
                            Reset Filter
                        </x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::section>

        {{-- CARD 2: Ringkasan Filter & Statistik (Hanya muncul setelah tombol ditekan) --}}
        @if($isFilterSubmitted)
        <x-filament::section>
            <x-slot name="heading">Ringkasan Pencarian</x-slot>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori Supplier</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $this->getSupplierCategoryLabel() }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelompok / Detail Produk</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                        @if(!empty($filterData['product_id']))
                            {{ \App\Models\Product::find($filterData['product_id'])?->nama_produk ?? '-' }}
                        @elseif(!empty($filterData['product_group_id']))
                            {{ \App\Models\ProductGroup::find($filterData['product_group_id'])?->nama_kelompok_produk ?? '-' }}
                        @else
                            Semua Produk
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode Data</p>
                    <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                        {{ \Carbon\Carbon::parse($filterData['period_start'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($filterData['period_end'])->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Statistik Kandidat</p>
                    @php
                        $total = count($candidates);
                        $aktif = collect($candidates)->where('is_active', true)->count();
                        $nonaktif = $total - $aktif;
                        $lengkap = collect($candidates)->where('is_active', true)->where('is_complete', true)->count();
                        $belumLengkap = $aktif - $lengkap;
                    @endphp
                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        Total: <strong>{{ $total }}</strong> (Aktif: {{ $aktif }}, Nonaktif: {{ $nonaktif }})<br>
                        Data Lengkap: <span class="text-green-600 font-bold">{{ $lengkap }}</span>, 
                        Belum Lengkap: <span class="text-orange-500 font-bold">{{ $belumLengkap }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- CARD 3: Kandidat Supplier --}}
        <x-filament::section>
            <x-slot name="heading">Kandidat Supplier</x-slot>
            <x-slot name="description">Pilih minimal 2 supplier aktif dengan data lengkap untuk disertakan dalam perhitungan.</x-slot>

            @if(count($candidates) === 0)
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-800 mb-4">
                        <x-heroicon-o-users class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tidak Ada Kandidat</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Tidak ada supplier yang ditemukan untuk kriteria filter tersebut.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 mt-4">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 w-10 text-center">Pilih</th>
                                <th class="px-4 py-3">Nama Supplier</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Trans.</th>
                                <th class="px-4 py-3 text-center" title="Kualitas Produk">C1</th>
                                <th class="px-4 py-3 text-center" title="Harga">C2</th>
                                <th class="px-4 py-3 text-center" title="Masa Kerja Sama">C3</th>
                                <th class="px-4 py-3 text-center" title="Kapasitas Pemenuhan">C4</th>
                                <th class="px-4 py-3 text-center" title="Pengiriman">C5</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($candidates as $c)
                                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" wire:model.live="selectedSuppliers" value="{{ $c['id'] }}" 
                                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                            @if(!$c['is_active'] || !$c['is_complete']) disabled @endif
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $c['nama_supplier'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $c['kode_supplier'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($c['is_active'])
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400">Aktif</span>
                                            @if(!$c['is_complete'])
                                                <div class="mt-1"><span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-[10px] font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">Data Belum Lengkap</span></div>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-400">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono">{{ $c['transaction_count'] }}</td>
                                    
                                    @foreach(['c1','c2','c3','c4','c5'] as $crit)
                                        <td class="px-4 py-3 text-center font-bold">
                                            @if($c[$crit])
                                                {{ $c[$crit] }}
                                            @else
                                                <span class="text-gray-300">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    
                                    <td class="px-4 py-3 text-center">
                                        <button 
                                            wire:click="mountAction('detailSupplier', { supplier_id: {{ $c['id'] }}, supplier_name: '{{ $c['nama_supplier'] }}' })"
                                            class="text-sm text-primary-600 hover:text-primary-500 font-medium transition-colors"
                                        >
                                            Lihat Data
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ count($selectedSuppliers) }} supplier terpilih.
                    </p>
                    @if(!$isRecaCalculated)
                    <x-filament::button wire:click="hitungBobotReca" icon="heroicon-m-calculator" size="lg" color="success" 
                        :disabled="count($selectedSuppliers) < 2">
                        Hitung Bobot RECA
                    </x-filament::button>
                    @endif
                </div>
            @endif
        </x-filament::section>
        @endif

        {{-- CARD 4: Hasil RECA (Hanya jika isRecaCalculated == true) --}}
        @if($isRecaCalculated && $currentCalculation)
        <x-filament::section>
            <x-slot name="heading">Hasil Pembobotan Kriteria (RECA)</x-slot>
            
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 mt-4">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">Rank</th>
                            <th class="px-4 py-3">Kriteria</th>
                            <th class="px-4 py-3 text-center">Atribut</th>
                            <th class="px-4 py-3 text-right">Bobot (W)</th>
                            <th class="px-4 py-3 text-right">Persentase</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($currentCalculation->recaDetails as $detail)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-3 text-center font-bold text-gray-500">{{ $detail->contribution_rank }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $detail->criteria_code }} - {{ $detail->criteria_name }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $detail->attribute == 'BENEFIT' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400' }}">
                                        {{ $detail->attribute }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-600 dark:text-gray-400">
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
                                        Detail & Perhitungan
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- CARD 5: Hasil MAUT (Hanya jika isRecaCalculated == true) --}}
        <x-filament::section>
            <x-slot name="heading">Hasil Perangkingan Supplier (MAUT)</x-slot>
            
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 mt-4">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-center w-16">Peringkat</th>
                            <th class="px-4 py-3">Nama Supplier</th>
                            <th class="px-4 py-3 text-right">Skor Akhir (Utilitas)</th>
                            <th class="px-4 py-3 text-center">Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($currentCalculation->mautRankings as $ranking)
                            <tr class="{{ $ranking->rank == 1 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-white dark:bg-gray-900' }}">
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-bold {{ $ranking->rank == 1 ? 'bg-green-500 text-white shadow-sm' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                        {{ $ranking->rank }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $ranking->supplier_name }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 font-mono">
                                    {{ number_format($ranking->final_score, 4) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($ranking->rank == 1)
                                        <span class="inline-flex items-center rounded-md bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                                            Direkomendasikan
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        <x-filament-actions::modals />

            {{-- CARD 6: Riwayat Perhitungan --}}
            @php
                $historicalCalculations = $this->getHistoricalCalculations();
            @endphp
            @if($historicalCalculations->total() > 0 || !empty($this->historySearch))
                <div class="mt-12 overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-700">
                    <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 dark:border-gray-700">
                        <h3 class="whitespace-nowrap text-lg font-semibold text-gray-950 dark:text-white">
                            Riwayat Perhitungan Terakhir
                        </h3>

                        <div class="relative w-full sm:w-[340px]">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                            </div>

                            <input
                                type="search"
                                wire:model.live.debounce.500ms="historySearch"
                                placeholder="Cari kode atau nama..."
                                class="block w-full rounded-lg border-gray-300 py-2 pl-10 pr-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-primary-500"
                            />

                            @if(!empty($this->historySearch))
                                <button wire:click="resetHistorySearch" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <x-heroicon-m-x-mark class="h-5 w-5" />
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3">Kode / Nama</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Periode Filter</th>
                                    <th class="px-4 py-3 text-center">Total Kandidat</th>
                                    <th class="px-4 py-3">Tanggal Dihitung</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($historicalCalculations as $history)
                                    <tr class="bg-white dark:bg-gray-900 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition" 
                                        wire:click="lihatDetail({{ $history->id }})"
                                        title="Klik untuk melihat detail perhitungan"
                                        wire:key="history-{{ $history->id }}">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $history->calculation_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $history->calculation_code }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $history->supplier_category }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                            {{ \Carbon\Carbon::parse($history->period_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($history->period_end)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-center">
                                            {{ $history->total_selected }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $history->calculated_at ? $history->calculated_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button wire:click.stop="lihatDetail({{ $history->id }})" class="text-primary-600 hover:text-primary-500" title="Detail">
                                                    <x-heroicon-o-eye class="w-5 h-5" />
                                                </button>
                                                <button wire:click.stop="" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" title="Cetak Laporan">
                                                    <x-heroicon-o-printer class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-800 mb-3">
                                                    <x-heroicon-o-document-magnifying-glass class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                                </div>
                                                <p class="text-base font-medium text-gray-900 dark:text-gray-100">Riwayat perhitungan tidak ditemukan.</p>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Coba gunakan nama atau kode perhitungan yang berbeda.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($historicalCalculations->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            {{ $historicalCalculations->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @else
        @include('filament.pages.perhitungan.detail-riwayat')
    @endif
</x-filament-panels::page>