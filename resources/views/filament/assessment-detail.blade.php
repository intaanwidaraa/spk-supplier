<div class="space-y-6">
    {{-- Header Info --}}
    <div class="grid grid-cols-2 gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Supplier</p>
            <p class="mt-1 text-base font-bold text-gray-900 dark:text-white">{{ $assessment->supplier?->nama_supplier ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Periode Evaluasi</p>
            <p class="mt-1 text-base font-bold text-gray-900 dark:text-white">{{ $assessment->evaluationPeriod?->evaluation_code ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Produk</p>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $assessment->product?->nama_produk ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal Perhitungan</p>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                {{ $assessment->calculated_at ? $assessment->calculated_at->format('d M Y, H:i') : '-' }}
            </p>
        </div>
    </div>

    {{-- Score Summary --}}
    <div class="grid grid-cols-5 gap-3">
        @foreach ([
            ['label' => 'C1', 'score' => $assessment->c1_score, 'name' => 'Kualitas Produk'],
            ['label' => 'C2', 'score' => $assessment->c2_score, 'name' => 'Harga'],
            ['label' => 'C3', 'score' => $assessment->c3_score, 'name' => 'Masa Kerja Sama'],
            ['label' => 'C4', 'score' => $assessment->c4_score, 'name' => 'Ketepatan Kuantitas'],
            ['label' => 'C5', 'score' => $assessment->c5_score, 'name' => 'Ketepatan Waktu'],
        ] as $s)
        @php
            $colorClass = match((int)($s['score'] ?? 0)) {
                5 => 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200',
                4 => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900 dark:text-blue-200',
                3 => 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-700 dark:text-gray-200',
                2 => 'bg-yellow-100 text-yellow-800 border-yellow-300 dark:bg-yellow-900 dark:text-yellow-200',
                1 => 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900 dark:text-red-200',
                default => 'bg-gray-100 text-gray-500 border-gray-200',
            };
        @endphp
        <div class="flex flex-col items-center rounded-lg border {{ $colorClass }} p-3 text-center">
            <span class="text-xs font-bold">{{ $s['label'] }}</span>
            <span class="my-1 text-2xl font-black">{{ $s['score'] ?? '-' }}</span>
            <span class="text-xs leading-tight opacity-80">{{ $s['name'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- Detail Per Criterion --}}
    <div class="space-y-4">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Detail Perhitungan Per Kriteria</h3>

        @php $details = $assessment->scoreDetails->keyBy('criterion_id'); @endphp

        @forelse($assessment->scoreDetails->load('criterion') as $detail)
        @php
            $isOverride = $detail->is_manual_override;
            $finalScore = $detail->final_score;
            $badgeColor = match((int)($finalScore ?? 0)) {
                5 => 'bg-green-500', 4 => 'bg-blue-500',
                3 => 'bg-gray-400', 2 => 'bg-yellow-500', 1 => 'bg-red-500',
                default => 'bg-gray-300',
            };
        @endphp

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Criterion Header --}}
            <div class="flex items-center justify-between bg-gray-100 px-4 py-3 dark:bg-gray-700/50">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $badgeColor }} text-sm font-bold text-white shadow">
                        {{ $finalScore ?? '-' }}
                    </span>
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                            {{ $detail->criterion?->kode_kriteria }} – {{ $detail->criterion?->nama_kriteria }}
                        </span>
                        @if($detail->score_category)
                        <span class="ml-2 rounded px-2 py-0.5 text-xs {{ $badgeColor }} bg-opacity-20 text-gray-700 dark:text-gray-300">
                            {{ $detail->score_category }}
                        </span>
                        @endif
                    </div>
                </div>
                @if($isOverride)
                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    ✏️ Dikoreksi Manual
                </span>
                @else
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                    🤖 Otomatis
                </span>
                @endif
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-3 text-sm">
                @if($detail->raw_value_label)
                <div>
                    <span class="font-medium text-gray-600 dark:text-gray-400">Data Mentah:</span>
                    <span class="ml-2 text-gray-800 dark:text-gray-200">{{ $detail->raw_value_label }}</span>
                </div>
                @endif

                @if($detail->calculation_description)
                <div class="rounded-lg bg-blue-50 px-3 py-2 dark:bg-blue-900/20">
                    <span class="font-medium text-blue-700 dark:text-blue-300 text-xs uppercase tracking-wide">Parameter Perhitungan:</span>
                    <p class="mt-1 text-gray-700 dark:text-gray-300 leading-relaxed">{{ $detail->calculation_description }}</p>
                </div>
                @endif

                <div class="flex gap-6">
                    <div>
                        <span class="text-xs text-gray-500">Skor Otomatis:</span>
                        <span class="ml-1 font-bold text-gray-800 dark:text-gray-200">{{ $detail->auto_score ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500">Skor Akhir:</span>
                        <span class="ml-1 font-bold text-gray-800 dark:text-gray-200">{{ $detail->final_score ?? '-' }}</span>
                    </div>
                </div>

                @if($isOverride && $detail->override_reason)
                <div class="rounded-lg bg-yellow-50 px-3 py-2 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700">
                    <span class="font-medium text-yellow-700 dark:text-yellow-300 text-xs uppercase tracking-wide">Alasan Koreksi:</span>
                    <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $detail->override_reason }}</p>
                    @if($detail->overridden_at)
                    <p class="mt-1 text-xs text-gray-500">Dikoreksi pada: {{ \Carbon\Carbon::parse($detail->overridden_at)->format('d M Y, H:i') }}</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-500 dark:border-gray-600 dark:text-gray-400">
            <p>Detail perhitungan belum tersedia. Jalankan Hitung Otomatis terlebih dahulu.</p>
        </div>
        @endforelse
    </div>
</div>
