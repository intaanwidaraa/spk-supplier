<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Livewire\WithPagination;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Calculation;
use App\Models\CalculationSupplier;
use App\Models\Supplier;
use App\Models\ProductGroup;
use App\Models\Product;
use App\Models\Criteria;
use App\Models\PurchaseHistory;
use App\Services\SupplierScoreCalculator;
use Carbon\Carbon;

class PerhitunganSupplier extends Page
{
    use WithPagination;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Perhitungan Supplier';
    protected static ?string $navigationGroup = 'Analisis dan Perhitungan';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Perhitungan Supplier';
    protected static ?string $slug = 'perhitungan-supplier';
    protected static string $view = 'filament.pages.perhitungan-supplier';

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin();
    }

    // State Variables
    public ?array $filterData = [];
    public bool $isFilterSubmitted = false;
    public array $candidates = [];
    public array $selectedSuppliers = [];
    public ?Calculation $currentCalculation = null;
    public bool $isRecaCalculated = false;
    public ?Calculation $viewingCalculation = null;
    
    public string $historySearch = '';

    public function updatingHistorySearch()
    {
        $this->resetPage();
    }

    public function resetHistorySearch()
    {
        $this->historySearch = '';
        $this->resetPage();
    }

    public function lihatDetail(int $id)
    {
        $calc = Calculation::with([
            'recaDetails', 
            'mautRankings' => fn($q) => $q->orderBy('rank'), 
            'recaSupplierDetails.supplier', 
            'selectedSuppliers'
        ])->find($id);

        if ($calc) {
            $this->viewingCalculation = $calc;
        } else {
            Notification::make()->title('Gagal')->body('Riwayat perhitungan tidak ditemukan.')->danger()->send();
        }
    }

    public function tutupDetail()
    {
        $this->viewingCalculation = null;
    }

    public function gunakanFilterIniLagi()
    {
        if ($this->viewingCalculation) {
            $this->resetFilter();
            
            $this->form->fill([
                'calculation_name' => '', // Reset the name so it's fresh
                'supplier_category' => $this->viewingCalculation->supplier_category,
                'product_group_id' => $this->viewingCalculation->product_group_id,
                'product_id' => $this->viewingCalculation->product_id,
                'period_type' => $this->viewingCalculation->period_type,
                'period_start' => $this->viewingCalculation->period_start,
                'period_end' => $this->viewingCalculation->period_end,
            ]);
            
            $this->tutupDetail();
        }
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getHistoricalCalculations()
    {
        $query = Calculation::with('productGroup', 'product')
            ->where('status', 'Final');

        if (!empty($this->historySearch)) {
            $search = '%' . $this->historySearch . '%';
            $query->where(function ($q) use ($search) {
                $q->where('calculation_name', 'like', $search)
                  ->orWhere('calculation_code', 'like', $search)
                  ->orWhere('supplier_category', 'like', $search)
                  ->orWhere('period_type', 'like', $search)
                  ->orWhere('status', 'like', $search)
                  ->orWhereHas('productGroup', function ($qGroup) use ($search) {
                      $qGroup->where('nama_kelompok_produk', 'like', $search);
                  });
            });
        }

        return $query->latest()->paginate(5);
    }

    public function getSupplierCategoryOptions(): array
    {
        return [
            'Raw Material' => 'Raw Material',
            'Packaging Material' => 'Packaging Material',
        ];
    }

    public function getSupplierCategoryLabel(): string
    {
        $val = $this->filterData['supplier_category'] ?? null;
        return $this->getSupplierCategoryOptions()[$val] ?? '-';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('calculation_name')
                        ->label('Nama Perhitungan (Opsional)')
                        ->placeholder('Contoh: Evaluasi Q1 2026')
                        ->columnSpan(3),
                        
                    Select::make('supplier_category')
                        ->label('Kategori Supplier')
                        ->options($this->getSupplierCategoryOptions())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('product_group_id', null)),
                        
                    Select::make('product_group_id')
                        ->label('Kelompok Produk (Opsional)')
                        ->options(function(callable $get) {
                            $cat = $get('supplier_category');
                            if (!$cat) return ProductGroup::pluck('nama_kelompok_produk', 'id');
                            return ProductGroup::where('kategori_produk', $cat)->pluck('nama_kelompok_produk', 'id');
                        })
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('product_id', null)),
                        
                    Select::make('product_id')
                        ->label('Produk Spesifik (Opsional)')
                        ->options(function(callable $get) {
                            $groupId = $get('product_group_id');
                            if (!$groupId) return [];
                            return Product::where('product_group_id', $groupId)->pluck('nama_produk', 'id');
                        })
                        ->disabled(fn(callable $get) => empty($get('product_group_id'))),
                ]),
                
                Grid::make(3)->schema([
                    Select::make('period_type')
                        ->label('Jenis Periode')
                        ->options([
                            'weekly' => 'Mingguan',
                            'monthly' => 'Bulanan',
                            'yearly' => 'Tahunan',
                            'custom' => 'Rentang Tanggal',
                        ])
                        ->required()
                        ->live(),
                        
                    DatePicker::make('period_start')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->displayFormat('d M Y'),
                        
                    DatePicker::make('period_end')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->displayFormat('d M Y')
                        ->afterOrEqual('period_start'),
                ])
            ])
            ->statePath('filterData');
    }

    public function tampilkanDataSupplier(SupplierScoreCalculator $calculator)
    {
        $data = $this->form->getState();
        
        $this->candidates = [];
        $this->selectedSuppliers = [];
        $this->currentCalculation = null;
        $this->isRecaCalculated = false;

        $query = Supplier::query()
            ->where('kategori', $data['supplier_category']);

        if (!empty($data['product_id'])) {
            $productId = $data['product_id'];
            $query->whereHas('products', function ($q) use ($productId) {
                $q->where('products.id', $productId);
            })->with([
                'products' => function ($q) use ($productId) {
                    $q->where('products.id', $productId);
                },
                'products.productGroup',
            ]);
        } elseif (!empty($data['product_group_id'])) {
            $productGroupId = $data['product_group_id'];
            $query->whereHas('products', function ($q) use ($productGroupId) {
                $q->where('products.product_group_id', $productGroupId);
            })->with([
                'products' => function ($q) use ($productGroupId) {
                    $q->where('products.product_group_id', $productGroupId);
                },
                'products.productGroup',
            ]);
        }

        $query->distinct();
        $suppliers = $query->get();            
        $start = Carbon::parse($data['period_start']);
        $end = Carbon::parse($data['period_end']);
        
        foreach ($suppliers as $sup) {
            $scores = $calculator->calculateForSupplier(
                $sup, 
                $start, 
                $end, 
                $data['product_group_id'] ?? null, 
                $data['product_id'] ?? null
            );
            
            $isComplete = isset($scores['scores']['C1']['score'], $scores['scores']['C2']['score'], $scores['scores']['C3']['score'], $scores['scores']['C4']['score'], $scores['scores']['C5']['score']);
            
            // Skip jika tidak ada histori dan C1 kosong
            if ($scores['transaction_count'] == 0 && empty($scores['scores']['C1']['score'])) {
                $isComplete = false;
            }
            
            $isActive = $sup->status_kerja_sama === 'Aktif';

            $candidate = [
                'id' => $sup->id,
                'kode_supplier' => $sup->kode_supplier,
                'nama_supplier' => $sup->nama_supplier,
                'status_kerja_sama' => $sup->status_kerja_sama,
                'transaction_count' => $scores['transaction_count'],
                'c1' => $scores['scores']['C1']['score'] ?? null,
                'c2' => $scores['scores']['C2']['score'] ?? null,
                'c3' => $scores['scores']['C3']['score'] ?? null,
                'c4' => $scores['scores']['C4']['score'] ?? null,
                'c5' => $scores['scores']['C5']['score'] ?? null,
                'scores_data' => $scores['scores'],
                'is_complete' => $isComplete,
                'is_active' => $isActive,
            ];

            $this->candidates[] = $candidate;

            if ($isActive && $isComplete) {
                $this->selectedSuppliers[] = $sup->id;
            }
        }
        
        $this->isFilterSubmitted = true;
    }

    public function hitungBobotReca()
    {
        if (count($this->selectedSuppliers) < 2) {
            Notification::make()->title('Gagal')->body('Minimal 2 supplier terpilih untuk melakukan pembobotan RECA.')->danger()->send();
            return;
        }

        // Validasi kelengkapan data supplier terpilih
        $selectedCandidates = collect($this->candidates)->whereIn('id', $this->selectedSuppliers);
        foreach ($selectedCandidates as $c) {
            if (!$c['is_complete']) {
                Notification::make()->title('Gagal')->body('Terdapat supplier terpilih dengan data belum lengkap. Hapus centang pada supplier tersebut.')->danger()->send();
                return;
            }
        }

        $data = $this->filterData;

        // 1. Buat record Calculation (Draft)
        $this->currentCalculation = Calculation::create([
            'calculation_name' => !empty($data['calculation_name']) ? $data['calculation_name'] : ('Perhitungan ' . date('d M Y H:i')),
            'supplier_category' => $data['supplier_category'],
            'product_group_id' => $data['product_group_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'period_type' => $data['period_type'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => 'Draft',
            'total_candidates' => count($this->candidates),
            'total_selected' => count($this->selectedSuppliers),
        ]);

        // 2. Simpan Snapshot CalculationSupplier
        foreach ($selectedCandidates as $c) {
            $sup = Supplier::find($c['id']);
            CalculationSupplier::create([
                'calculation_id' => $this->currentCalculation->id,
                'supplier_id' => $c['id'],
                'supplier_code' => $c['kode_supplier'],
                'supplier_name' => $c['nama_supplier'],
                'supplier_status' => $c['status_kerja_sama'],
                'partnership_category' => $sup->partnership_category,
                'masa_kerja_sama' => $sup->masa_kerja_sama,
                'tanggal_awal_kerja_sama' => $sup->tanggal_awal_kerja_sama,
                'c1_score' => $c['c1'],
                'c2_score' => $c['c2'],
                'c3_score' => $c['c3'],
                'c4_score' => $c['c4'],
                'c5_score' => $c['c5'],
                'c1_data' => $c['scores_data']['C1']['data'],
                'c2_data' => $c['scores_data']['C2']['data'],
                'c3_data' => $c['scores_data']['C3']['data'],
                'c4_data' => $c['scores_data']['C4']['data'],
                'c5_data' => $c['scores_data']['C5']['data'],
                'is_selected' => true,
                'transaction_count' => $c['transaction_count']
            ]);
        }
        
        $this->currentCalculation->load('selectedSuppliers');

        // 3. Hitung RECA-MAUT via API Python
        $this->callApiCalculation($this->currentCalculation);
    }
    
    protected function callApiCalculation(Calculation $calc)
    {
        $criteria = Criteria::all()->map(function($c) {
            return [
                'code' => $c->kode_kriteria,
                'name' => $c->nama_kriteria,
                'attribute' => strtolower($c->atribut),
            ];
        })->toArray();
        
        $suppliersData = [];
        foreach ($calc->selectedSuppliers as $sup) {
            $suppliersData[] = [
                'supplier_id' => $sup->supplier_id,
                'supplier_name' => $sup->supplier_name,
                'scores' => [
                    'C1' => $sup->c1_score,
                    'C2' => $sup->c2_score,
                    'C3' => $sup->c3_score,
                    'C4' => $sup->c4_score,
                    'C5' => $sup->c5_score,
                ]
            ];
        }
        
        $payload = [
            'evaluation_code' => $calc->calculation_code,
            'supplier_category' => $calc->supplier_category,
            'product_group' => null,
            'criteria' => $criteria,
            'suppliers' => $suppliersData
        ];
        
        try {
            $response = Http::post('http://127.0.0.1:8001/calculate-evaluation', $payload);
            
            if ($response->successful()) {
                $result = $response->json();
                if ($result['success']) {
                    DB::transaction(function() use ($calc, $result) {
                        foreach ($result['reca']['weights'] as $index => $w) {
                            $code = $w['criteria_code'];
                            $steps = $result['reca']['steps'] ?? [];
                            
                            $calc->recaDetails()->create([
                                'criteria_code' => $code,
                                'criteria_name' => $w['criteria_name'],
                                'attribute' => $w['attribute'],
                                'weight' => $w['weight'],
                                'weight_percentage' => $w['weight'] * 100,
                                'geometric_mean' => $steps['geometric_means'][$code] ?? null,
                                'standard_value' => $steps['standard_values'][$code] ?? null,
                                'variation_value' => $steps['variation_values'][$code] ?? null,
                                'deviation_value' => $steps['preference_deviation'][$code] ?? null,
                                'contribution_rank' => $index + 1
                            ]);
                        }
                        
                        if (isset($result['reca']['steps']['preference_matrix'])) {
                            foreach ($result['reca']['steps']['preference_matrix'] as $pmat) {
                                foreach ($pmat['values'] as $code => $pv) {
                                    $calc->recaSupplierDetails()->create([
                                        'supplier_id' => $pmat['supplier_id'],
                                        'criteria_code' => $code,
                                        'pv_ij' => $pv,
                                    ]);
                                }
                            }
                        }
                        
                        foreach ($result['maut']['rankings'] as $r) {
                            $calc->mautRankings()->create([
                                'supplier_id' => $r['supplier_id'],
                                'supplier_name' => $r['supplier_name'],
                                'final_score' => $r['final_score'],
                                'rank' => $r['rank'],
                                'normalized_scores' => $r['normalized_scores'],
                                'weighted_scores' => $r['weighted_scores'],
                            ]);
                        }
                        
                        $calc->update([
                            'status' => 'Final',
                            'calculated_at' => now(),
                        ]);
                    });
                    
                    Notification::make()->title('Perhitungan Berhasil')->success()->send();
                    $this->currentCalculation->refresh();
                    $this->currentCalculation->load(['recaDetails', 'mautRankings' => fn($q) => $q->orderBy('rank'), 'recaSupplierDetails.supplier', 'selectedSuppliers']);
                    $this->isRecaCalculated = true;
                    
                } else {
                    throw new \Exception("Response API tidak sukses.");
                }
            } else {
                throw new \Exception("Gagal menghubungi API: " . $response->body());
            }
        } catch (\Exception $e) {
            Notification::make()->title('API Error')->body($e->getMessage())->danger()->send();
            $calc->update(['status' => 'Draft']);
        }
    }

    public function resetFilter()
    {
        $this->form->fill();
        $this->isFilterSubmitted = false;
        $this->isRecaCalculated = false;
        $this->candidates = [];
        $this->selectedSuppliers = [];
        $this->currentCalculation = null;
    }

    // SLIDE OVER ACTIONS
    public function detailKriteriaAction(): Action
    {
        return Action::make('detailKriteria')
            ->slideOver()
            ->modalWidth('7xl')
            ->modalHeading(fn (array $arguments) => 'Detail Perhitungan: ' . ($arguments['criteria_code'] ?? ''))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(function (array $arguments) {
                $calc = $this->viewingCalculation ?? $this->currentCalculation;
                if (!$calc) return null;
                $code = $arguments['criteria_code'] ?? null;
                if (!$code) return null;

                $detail = $calc->recaDetails->where('criteria_code', $code)->first();
                $criterion = Criteria::with('scoreGuidelines')->where('kode_kriteria', $code)->first();

                return view('filament.pages.perhitungan.criteria-slideover', [
                    'calculation' => $calc,
                    'detail' => $detail,
                    'criterion' => $criterion,
                ]);
            });
    }

    public function detailRiwayatSupplierAction(): Action
    {
        return Action::make('detailRiwayatSupplier')
            ->slideOver()
            ->modalWidth('5xl')
            ->modalHeading(fn (array $arguments) => 'Data Pembentuk Skor (Snapshot): ' . ($arguments['supplier_name'] ?? ''))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(function (array $arguments) {
                $id = $arguments['calculation_supplier_id'] ?? null;
                if (!$id) return null;
                
                $calcSupplier = CalculationSupplier::find($id);
                if (!$calcSupplier) return null;

                $supplier = $calcSupplier->supplier;

                $candidateData = [
                    'scores_data' => [
                        'C1' => ['score' => $calcSupplier->c1_score, 'data' => is_string($calcSupplier->c1_data) ? json_decode($calcSupplier->c1_data, true) : $calcSupplier->c1_data],
                        'C2' => ['score' => $calcSupplier->c2_score, 'data' => is_string($calcSupplier->c2_data) ? json_decode($calcSupplier->c2_data, true) : $calcSupplier->c2_data],
                        'C3' => ['score' => $calcSupplier->c3_score, 'data' => is_string($calcSupplier->c3_data) ? json_decode($calcSupplier->c3_data, true) : $calcSupplier->c3_data],
                        'C4' => ['score' => $calcSupplier->c4_score, 'data' => is_string($calcSupplier->c4_data) ? json_decode($calcSupplier->c4_data, true) : $calcSupplier->c4_data],
                        'C5' => ['score' => $calcSupplier->c5_score, 'data' => is_string($calcSupplier->c5_data) ? json_decode($calcSupplier->c5_data, true) : $calcSupplier->c5_data],
                    ]
                ];

                return view('filament.pages.perhitungan.supplier-slideover', [
                    'supplier' => $supplier ?? (new Supplier(['nama_supplier' => $calcSupplier->supplier_name, 'kode_supplier' => $calcSupplier->supplier_code, 'status_kerja_sama' => $calcSupplier->supplier_status, 'masa_kerja_sama' => $calcSupplier->masa_kerja_sama])),
                    'candidate' => $candidateData,
                    'transactions' => []
                ]);
            });
    }

    public function detailSupplierAction(): Action
    {
        return Action::make('detailSupplier')
            ->slideOver()
            ->modalWidth('5xl')
            ->modalHeading(fn (array $arguments) => 'Data Aktual & Transaksi: ' . ($arguments['supplier_name'] ?? ''))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(function (array $arguments) {
                $id = $arguments['supplier_id'] ?? null;
                if (!$id) return null;
                
                $supplier = Supplier::find($id);
                // get the computed candidate data from state
                $candidateData = collect($this->candidates)->firstWhere('id', $id);
                
                // Get transactions within the filter period
                $query = PurchaseHistory::where('supplier_id', $id)
                    ->whereBetween('tanggal_pembelian', [$this->filterData['period_start'], $this->filterData['period_end']]);
                    
                if (!empty($this->filterData['product_id'])) {
                    $product = Product::find($this->filterData['product_id']);
                    if ($product) {
                        $query->where(function($q) use ($product) {
                            $q->where('kode_produk', $product->kode_produk)
                              ->orWhere('nama_produk', 'like', '%' . $product->nama_produk . '%');
                        });
                    }
                } elseif (!empty($this->filterData['product_group_id'])) {
                    $group = ProductGroup::find($this->filterData['product_group_id']);
                    if ($group) {
                        $productNames = $group->products()->pluck('nama_produk');
                        if ($productNames->isEmpty()) {
                            $productNames = collect([$group->nama_kelompok_produk]);
                        }
                        $query->where(function($q) use ($productNames) {
                            foreach ($productNames as $name) {
                                $q->orWhere('nama_produk', 'like', '%' . $name . '%');
                            }
                        });
                    }
                }
                
                $transactions = $query->latest('tanggal_pembelian')->limit(100)->get();

                return view('filament.pages.perhitungan.supplier-slideover', [
                    'supplier' => $supplier,
                    'candidate' => $candidateData,
                    'transactions' => $transactions,
                ]);
            });
    }
}