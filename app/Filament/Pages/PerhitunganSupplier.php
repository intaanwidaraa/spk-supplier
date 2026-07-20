<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PerhitunganSupplier extends Page
{
    protected static ?string $navigationIcon =
        'heroicon-o-calculator';

    protected static ?string $navigationLabel =
        'Perhitungan Supplier';

    protected static ?string $navigationGroup =
        'Proses Evaluasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title =
        'Perhitungan Supplier';

    protected static ?string $slug =
        'perhitungan-supplier';

    protected static string $view =
        'filament.pages.perhitungan-supplier';

    public ?int $evaluation_period_id = null;
    public ?string $product_category = null;
    public ?int $product_group_id = null;
    
    public ?\App\Models\EvaluationResult $evaluationResult = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->loadLatestResult();
    }
    
    public function loadLatestResult()
    {
        $this->evaluationResult = \App\Models\EvaluationResult::with(['recaWeights', 'mautRankings' => function($q) {
            $q->orderBy('rank', 'asc');
        }])->latest()->first();
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make('Parameter Perhitungan')
                    ->schema([
                        \Filament\Forms\Components\Select::make('evaluation_period_id')
                            ->label('Periode Evaluasi')
                            ->options(\App\Models\EvaluationPeriod::pluck('evaluation_code', 'id'))
                            ->required(),
                        \Filament\Forms\Components\Select::make('product_category')
                            ->label('Kategori Supplier')
                            ->options([
                                'Raw Material' => 'Raw Material',
                                'Packaging Material' => 'Packaging Material',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Select::make('product_group_id')
                            ->label('Kelompok Produk (Opsional)')
                            ->options(\App\Models\ProductGroup::pluck('nama_kelompok_produk', 'id')),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('hitung')
                ->label('Hitung RECA-MAUT')
                ->icon('heroicon-m-calculator')
                ->color('primary')
                ->action(function () {
                    $data = $this->form->getState();
                    
                    if (empty($data['evaluation_period_id']) || empty($data['product_category'])) {
                        \Filament\Notifications\Notification::make()
                            ->title('Validasi Gagal')
                            ->body('Pilih Periode Evaluasi dan Kategori Supplier.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $periodId = $data['evaluation_period_id'];
                    $category = $data['product_category'];
                    $groupId = $data['product_group_id'] ?? null;
                    
                    $period = \App\Models\EvaluationPeriod::find($periodId);
                    
                    // Ambil criteria
                    $criteriaList = \App\Models\Criteria::all()->map(function($c) {
                        return [
                            'code' => $c->kode_kriteria,
                            'name' => $c->nama_kriteria,
                            'attribute' => strtolower($c->atribut),
                        ];
                    })->toArray();
                    
                    // Ambil assessments (yang sudah dihitung via service)
                    $assessmentsQuery = \App\Models\SupplierPerformanceAssessment::with('supplier')
                        ->where('evaluation_period_id', $periodId)
                        ->where('product_category', $category);
                        
                    // Jika data ada product_group_id di DB, filter. (Tapi sebelumnya db kita buat null)
                    // Jadi kita biarkan saja filter berdasarkan category dan period.
                        
                    $assessments = $assessmentsQuery->get();
                    
                    if ($assessments->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Data Penilaian Kosong')
                            ->body('Tidak ada data penilaian untuk periode ini. Buka halaman Data Penilaian Kinerja Supplier terlebih dahulu.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $suppliersData = [];
                    foreach ($assessments as $ast) {
                        $suppliersData[] = [
                            'supplier_id' => $ast->supplier_id,
                            'supplier_name' => $ast->supplier->nama_supplier ?? 'Unknown',
                            'scores' => [
                                'C1' => $ast->c1_score ?? 0,
                                'C2' => $ast->c2_score ?? 0,
                                'C3' => $ast->c3_score ?? 0,
                                'C4' => $ast->c4_score ?? 0,
                                'C5' => $ast->c5_score ?? 0,
                            ]
                        ];
                    }
                    
                    $payload = [
                        'evaluation_code' => $period->evaluation_code,
                        'supplier_category' => $category,
                        'product_group' => $groupId ? \App\Models\ProductGroup::find($groupId)->nama_kelompok_produk : null,
                        'criteria' => $criteriaList,
                        'suppliers' => $suppliersData
                    ];
                    
                    try {
                        $response = \Illuminate\Support\Facades\Http::post('http://127.0.0.1:8001/calculate-evaluation', $payload);
                        
                        if ($response->successful()) {
                            $result = $response->json();
                            if ($result['success']) {
                                \Illuminate\Support\Facades\DB::transaction(function() use ($result, $periodId, $category, $groupId) {
                                    $evalResult = \App\Models\EvaluationResult::create([
                                        'evaluation_period_id' => $periodId,
                                        'supplier_category' => $category,
                                        'product_group_id' => $groupId,
                                    ]);
                                    
                                    foreach ($result['reca']['weights'] as $w) {
                                        $evalResult->recaWeights()->create([
                                            'criteria_code' => $w['criteria_code'],
                                            'weight' => $w['weight'],
                                        ]);
                                    }
                                    
                                    foreach ($result['maut']['rankings'] as $r) {
                                        $evalResult->mautRankings()->create([
                                            'supplier_id' => $r['supplier_id'],
                                            'supplier_name' => $r['supplier_name'],
                                            'final_score' => $r['final_score'],
                                            'rank' => $r['rank'],
                                            'normalized_scores' => $r['normalized_scores'],
                                            'weighted_scores' => $r['weighted_scores'],
                                        ]);
                                    }
                                });
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Perhitungan Selesai')
                                    ->success()
                                    ->send();
                                    
                                $this->loadLatestResult();
                            } else {
                                throw new \Exception("Response API tidak sukses.");
                            }
                        } else {
                            throw new \Exception("Gagal menghubungi Python API: " . $response->body());
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('API Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}