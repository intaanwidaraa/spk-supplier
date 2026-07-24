<div x-data="{ activeTab: 'dasar-penilaian' }">
    <x-filament::tabs label="Detail Kriteria">
        <x-filament::tabs.item 
            alpine-active="activeTab === 'dasar-penilaian'"
            x-on:click="activeTab = 'dasar-penilaian'"
        >
            Dasar Penilaian
        </x-filament::tabs.item>
        
        <x-filament::tabs.item 
            alpine-active="activeTab === 'data-pembentuk'"
            x-on:click="activeTab = 'data-pembentuk'"
        >
            Data Pembentuk Skor
        </x-filament::tabs.item>
        
        <x-filament::tabs.item 
            alpine-active="activeTab === 'proses-reca'"
            x-on:click="activeTab = 'proses-reca'"
        >
            Proses RECA
        </x-filament::tabs.item>
        
        <x-filament::tabs.item 
            alpine-active="activeTab === 'penjelasan'"
            x-on:click="activeTab = 'penjelasan'"
        >
            Penjelasan Hasil
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-6">
        {{-- TAB 1: Dasar Penilaian --}}
        <div x-show="activeTab === 'dasar-penilaian'" class="space-y-4">
            <div class="rounded-xl bg-gray-50 p-4 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ $criterion->nama_kriteria }} ({{ $criterion->kode_kriteria }})</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $criterion->short_description }}</p>
                <div class="mt-3">
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400">
                        Atribut: {{ $criterion->atribut }}
                    </span>
                </div>
            </div>

            <h4 class="font-bold text-gray-900 dark:text-gray-100">Pedoman Penilaian Skor</h4>
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-center">Skor</th>
                            <th class="px-4 py-3">Sub Kriteria</th>
                            <th class="px-4 py-3">Parameter Kuantitatif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($criterion->scoreGuidelines->sortByDesc('score') as $guideline)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-3 text-center font-bold">{{ $guideline->score }}</td>
                            <td class="px-4 py-3 font-medium">{{ $guideline->subcriteria }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $guideline->quantitative_parameter }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: Data Pembentuk Skor --}}
        <div x-show="activeTab === 'data-pembentuk'" style="display: none;" class="space-y-4">
            <h4 class="font-bold text-gray-900 dark:text-gray-100">Data Aktual Supplier</h4>
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 text-center">Skor C-Level</th>
                            <th class="px-4 py-3">Data Agregasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($calculation->selectedSuppliers as $sup)
                        @php
                            $cScore = $sup->{strtolower($criterion->kode_kriteria) . '_score'};
                            $cData = $sup->{strtolower($criterion->kode_kriteria) . '_data'};
                            $badgeColor = match((int)($cScore ?? 0)) {
                                5 => 'bg-green-500', 4 => 'bg-blue-500',
                                3 => 'bg-gray-400', 2 => 'bg-yellow-500', 1 => 'bg-red-500',
                                default => 'bg-gray-300',
                            };
                        @endphp
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-3 font-medium">{{ $sup->supplier_name }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $badgeColor }} text-sm font-bold text-white shadow">
                                    {{ $cScore ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs font-mono">
                                {{ $cData['label'] ?? json_encode($cData) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: Proses RECA --}}
        <div x-show="activeTab === 'proses-reca'" style="display: none;" class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-gray-500">Geometric Mean (GM)</p>
                    <p class="mt-1 text-sm font-mono">{{ number_format($detail->geometric_mean, 6) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-gray-500">Nilai Standar (N)</p>
                    <p class="mt-1 text-sm font-mono">{{ number_format($detail->standard_value, 6) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-gray-500">Nilai Variasi (φ)</p>
                    <p class="mt-1 text-sm font-mono">{{ number_format($detail->variation_value, 6) }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-700 dark:bg-blue-900/20">
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">Bobot Akhir (W)</p>
                    <p class="mt-1 text-lg font-bold text-blue-700 dark:text-blue-300">{{ number_format($detail->weight, 4) }}</p>
                </div>
            </div>

            <h4 class="font-bold text-gray-900 dark:text-gray-100 mt-6">Matrix Keputusan Kriteria Ini</h4>
            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 text-right">Nilai Matriks (X)</th>
                            <th class="px-4 py-3 text-right">Preference Value (PV)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-mono text-xs">
                        @foreach($calculation->recaSupplierDetails->where('criteria_code', $criterion->kode_kriteria) as $recaSup)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-3 font-sans">{{ $recaSup->supplier->nama_supplier ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                @php
                                    // Ambil C Score dari Supplier sebagai nilai X
                                    $s = $calculation->selectedSuppliers->where('supplier_id', $recaSup->supplier_id)->first();
                                    $x = $s ? $s->{strtolower($criterion->kode_kriteria) . '_score'} : 0;
                                @endphp
                                {{ $x }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($recaSup->pv_ij, 6) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 4: Penjelasan Hasil --}}
        <div x-show="activeTab === 'penjelasan'" style="display: none;" class="space-y-4">
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p>
                    Berdasarkan perhitungan RECA (Rank-order Evaluation and Consistency Assessment), kriteria <strong>{{ $criterion->nama_kriteria }}</strong> 
                    memperoleh bobot akhir sebesar <strong>{{ number_format($detail->weight_percentage, 2) }}%</strong>.
                </p>
                <p>
                    Langkah-langkah yang dilakukan:
                </p>
                <ol>
                    <li>Membentuk matriks keputusan (X) dari skor C-Level (1-5) dari masing-masing supplier.</li>
                    <li>Menghitung rata-rata ukur atau <em>Geometric Mean</em> (GM) dari seluruh nilai supplier untuk kriteria ini. GM yang didapat adalah {{ number_format($detail->geometric_mean, 4) }}.</li>
                    <li>Membentuk matriks preferensi (PV) dengan membagi nilai X dengan GM.</li>
                    <li>Melakukan normalisasi matriks preferensi (R) dengan memperhatikan sifat kriteria ({{ $criterion->atribut }}).</li>
                    <li>Menghitung Nilai Standar (N) dengan mencari rata-rata dari matriks R per kriteria.</li>
                    <li>Menghitung Nilai Variasi (φ) yang merepresentasikan penyebaran/simpangan nilai (mirip varians).</li>
                    <li>Mengonversi Nilai Variasi menjadi Bobot (W) melalui proporsi deviasi preferensi terhadap total deviasi seluruh kriteria.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
