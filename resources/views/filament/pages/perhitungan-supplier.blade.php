<x-filament-panels::page>
    <x-filament-panels::form>
        {{ $this->form }}
    </x-filament-panels::form>
    
    @if($evaluationResult)
        <x-filament::section>
            <x-slot name="heading">
                Hasil Perhitungan RECA & MAUT 
                (Kategori: {{ $evaluationResult->supplier_category }}, Periode: {{ $evaluationResult->evaluation_period_id }})
            </x-slot>

            <div class="mb-6">
                <h3 class="text-lg font-bold mb-2">Bobot Kriteria (RECA)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="p-2 border">Kode Kriteria</th>
                                <th class="p-2 border">Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evaluationResult->recaWeights as $weight)
                                <tr class="border-b">
                                    <td class="p-2 border">{{ $weight->criteria_code }}</td>
                                    <td class="p-2 border">{{ number_format($weight->weight, 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-2">Peringkat Supplier (MAUT)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="p-2 border">Peringkat</th>
                                <th class="p-2 border">Supplier</th>
                                <th class="p-2 border">Skor Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evaluationResult->mautRankings as $ranking)
                                <tr class="border-b {{ $ranking->rank == 1 ? 'bg-green-50' : '' }}">
                                    <td class="p-2 border text-center font-bold text-lg">{{ $ranking->rank }}</td>
                                    <td class="p-2 border font-semibold">{{ $ranking->supplier_name }}</td>
                                    <td class="p-2 border">{{ number_format($ranking->final_score, 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="text-center text-gray-500 py-8">
                Belum ada data hasil perhitungan. Silakan isi form di atas dan klik "Hitung RECA-MAUT".
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>