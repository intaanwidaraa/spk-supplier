<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Illuminate\Support\Facades\DB;
use App\Models\Calculation;
use App\Models\Supplier;
use App\Models\ProductGroup;
use App\Models\Product;
use App\Models\PurchaseHistory;
use App\Services\SupplierScoreCalculator;
use Carbon\Carbon;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Support\Enums\MaxWidth;

class LaporanTerpusat extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Terpusat';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Laporan Terpusat';
    protected static ?string $slug = 'laporan-terpusat';
    protected static string $view = 'filament.pages.laporan-terpusat';

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isDirektur();
    }

    public ?string $jenis_laporan = null;
    public ?array $filterData = [];
    public bool $isDataLoaded = false;
    public array $reportData = [];

    public function mount(): void
    {
        $this->form->fill([
            'jenis_laporan' => request('jenis_laporan'),
            'calculation_id' => request('calculation_id'),
        ]);
        
        if (request('jenis_laporan') && request('calculation_id')) {
            $this->tampilkanLaporan(app(\App\Services\SupplierScoreCalculator::class));
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ruang Lingkup Laporan')
                    ->description('Pilih jenis laporan dan atur filter data.')
                    ->schema([
                        Select::make('jenis_laporan')
                            ->label('Jenis Laporan')
                            ->options([
                                'evaluasi' => 'Hasil Evaluasi Supplier RECA–MAUT',
                                'ranking' => 'Perankingan Supplier MAUT',
                                'pembobotan' => 'Pembobotan Kriteria RECA',
                                'penilaian' => 'Penilaian Kinerja Supplier',
                                'historis' => 'Data Historis Pembelian',
                                'riwayat' => 'Riwayat Perhitungan',
                                'supplier' => 'Data Supplier',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                // Reset all filters when report type changes
                                $set('kategori', null);
                                $set('product_group_id', null);
                                $set('product_id', null);
                                $set('calculation_id', null);
                                $set('period_start', null);
                                $set('period_end', null);
                                $set('batas_ranking', 'Semua');
                                $set('status', null);
                                $set('supplier_id', null);
                                $this->isDataLoaded = false;
                                $this->reportData = [];
                            }),

                        Grid::make(3)
                            ->schema(fn (Get $get) => $this->getDynamicFilterSchema($get('jenis_laporan')))
                            ->visible(fn (Get $get) => filled($get('jenis_laporan'))),
                    ])
            ])
            ->statePath('filterData');
    }

    protected function getDynamicFilterSchema(?string $jenis_laporan): array
    {
        if (!$jenis_laporan) return [];

        $schema = [];

        // Common Fields
        $kategoriField = Select::make('kategori')
            ->label('Kategori Supplier')
            ->options([
                'Raw Material' => 'Raw Material',
                'Packaging Material' => 'Packaging Material',
            ])
            ->live()
            ->afterStateUpdated(fn(callable $set) => $set('product_group_id', null));

        $kelompokField = Select::make('product_group_id')
            ->label('Kelompok Produk')
            ->options(function(Get $get) {
                $cat = $get('kategori');
                if (!$cat) return ProductGroup::pluck('nama_kelompok_produk', 'id');
                return ProductGroup::where('kategori_produk', $cat)->pluck('nama_kelompok_produk', 'id');
            })
            ->live()
            ->afterStateUpdated(fn(callable $set) => $set('product_id', null));

        $detailProdukField = Select::make('product_id')
            ->label('Detail Produk')
            ->options(function(Get $get) {
                $groupId = $get('product_group_id');
                if (!$groupId) return [];
                return Product::where('product_group_id', $groupId)->pluck('nama_produk', 'id');
            });

        $calculationField = Select::make('calculation_id')
            ->label('Kode Perhitungan')
            ->options(function() {
                return Calculation::where('status', 'Final')
                    ->latest()
                    ->get()
                    ->mapWithKeys(fn($c) => [$c->id => $c->calculation_code . ' - ' . $c->calculation_name])
                    ->toArray();
            })
            ->searchable()
            ->required();

        $startDateField = DatePicker::make('period_start')
            ->label('Tanggal Mulai')
            ->required();

        $endDateField = DatePicker::make('period_end')
            ->label('Tanggal Selesai')
            ->required()
            ->afterOrEqual('period_start');

        switch ($jenis_laporan) {
            case 'evaluasi':
                $schema = [
                    $calculationField,
                    Select::make('batas_ranking')
                        ->label('Batas Ranking')
                        ->options(['Top 5' => 'Top 5', 'Top 10' => 'Top 10', 'Semua' => 'Semua'])
                        ->default('Semua'),
                ];
                break;
                
            case 'ranking':
                $schema = [
                    $calculationField,
                    Select::make('batas_ranking')
                        ->label('Batas Ranking')
                        ->options(['Top 5' => 'Top 5', 'Top 10' => 'Top 10', 'Semua' => 'Semua'])
                        ->default('Semua'),
                ];
                break;

            case 'pembobotan':
                $schema = [
                    $calculationField,
                ];
                break;

            case 'penilaian':
                $schema = [
                    $kategoriField->required(),
                    $kelompokField,
                    $detailProdukField,
                    $startDateField,
                    $endDateField,
                ];
                break;

            case 'historis':
                $schema = [
                    $kategoriField,
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(function(Get $get) {
                            $cat = $get('kategori');
                            $query = Supplier::query();
                            if ($cat) $query->where('kategori', $cat);
                            return $query->pluck('nama_supplier', 'id');
                        })
                        ->searchable(),
                    $kelompokField,
                    $detailProdukField,
                    $startDateField,
                    $endDateField,
                ];
                break;

            case 'riwayat':
                $schema = [
                    $kategoriField,
                    $kelompokField,
                    Select::make('status')
                        ->label('Status Perhitungan')
                        ->options(['Final' => 'Final', 'Draft' => 'Draft']),
                ];
                break;

            case 'supplier':
                $schema = [
                    $kategoriField,
                    $kelompokField,
                    $detailProdukField,
                    Select::make('status')
                        ->label('Status')
                        ->options(['Aktif' => 'Aktif', 'Nonaktif' => 'Nonaktif']),
                ];
                break;
        }

        return $schema;
    }

    public function tampilkanLaporan(SupplierScoreCalculator $calculator)
    {
        $this->form->getState(); // Validate
        $data = $this->filterData;
        $jenis = $data['jenis_laporan'];

        $this->reportData = [];

        try {
            if (in_array($jenis, ['evaluasi', 'ranking', 'pembobotan'])) {
                $calc = Calculation::with([
                    'recaDetails' => fn($q) => $q->orderBy('contribution_rank'), 
                    'mautRankings' => fn($q) => $q->orderBy('rank'),
                    'selectedSuppliers'
                ])->findOrFail($data['calculation_id']);
                
                $this->reportData['calculation'] = $calc;

                if ($jenis == 'evaluasi' || $jenis == 'ranking') {
                    $limit = $data['batas_ranking'] === 'Top 5' ? 5 : ($data['batas_ranking'] === 'Top 10' ? 10 : 999);
                    $this->reportData['rankings'] = $calc->mautRankings->take($limit);
                }
            } elseif ($jenis == 'penilaian') {
                $suppliers = Supplier::where('kategori', $data['kategori'])->get();
                $start = Carbon::parse($data['period_start']);
                $end = Carbon::parse($data['period_end']);
                
                $results = [];
                foreach ($suppliers as $sup) {
                    $scores = $calculator->calculateForSupplier(
                        $sup, 
                        $start, 
                        $end, 
                        $data['product_group_id'] ?? null, 
                        $data['product_id'] ?? null
                    );
                    
                    $c1 = $scores['scores']['C1']['score'] ?? 0;
                    $c2 = $scores['scores']['C2']['score'] ?? 0;
                    $c3 = $scores['scores']['C3']['score'] ?? 0;
                    $c4 = $scores['scores']['C4']['score'] ?? 0;
                    $c5 = $scores['scores']['C5']['score'] ?? 0;
                    $total = $c1 + $c2 + $c3 + $c4 + $c5;
                    $isComplete = $c1 && $c2 && $c3 && $c4 && $c5;
                    
                    if ($scores['transaction_count'] == 0 && empty($c1)) continue;

                    $results[] = [
                        'supplier_code' => $sup->kode_supplier,
                        'supplier_name' => $sup->nama_supplier,
                        'kategori' => $sup->kategori,
                        'c1' => $c1, 'c2' => $c2, 'c3' => $c3, 'c4' => $c4, 'c5' => $c5,
                        'total' => $total,
                        'status_data' => $isComplete ? 'Lengkap' : 'Tidak Lengkap',
                    ];
                }
                
                usort($results, fn($a, $b) => $b['total'] <=> $a['total']);
                $this->reportData['penilaian'] = $results;
                
            } elseif ($jenis == 'historis') {
                $query = PurchaseHistory::query();
                
                if (!empty($data['supplier_id'])) $query->where('supplier_id', $data['supplier_id']);
                
                if (!empty($data['product_id'])) {
                    $prod = Product::find($data['product_id']);
                    if ($prod) $query->where('kode_produk', $prod->kode_produk)->orWhere('nama_produk', 'like', '%'.$prod->nama_produk.'%');
                } elseif (!empty($data['product_group_id'])) {
                    $grp = ProductGroup::find($data['product_group_id']);
                    if ($grp) {
                        $names = $grp->products()->pluck('nama_produk');
                        if ($names->isEmpty()) $names = collect([$grp->nama_kelompok_produk]);
                        $query->where(function($q) use ($names) {
                            foreach($names as $n) $q->orWhere('nama_produk', 'like', '%'.$n.'%');
                        });
                    }
                }
                
                if (!empty($data['period_start']) && !empty($data['period_end'])) {
                    $query->whereBetween('tanggal_pembelian', [$data['period_start'], $data['period_end']]);
                }
                
                $this->reportData['historis'] = $query->latest('tanggal_pembelian')->get();
                
            } elseif ($jenis == 'riwayat') {
                $query = Calculation::with('productGroup', 'product')->latest();
                if (!empty($data['kategori'])) $query->where('supplier_category', $data['kategori']);
                if (!empty($data['product_group_id'])) $query->where('product_group_id', $data['product_group_id']);
                if (!empty($data['status'])) $query->where('status', $data['status']);
                
                $this->reportData['riwayat'] = $query->get();
                
            } elseif ($jenis == 'supplier') {
                // Gunakan service tersentralisasi
                $this->reportData['supplier'] = \App\Services\ReportService::getSupplierReportQuery($data)->get();
            }
            
            $this->isDataLoaded = true;
        } catch (\Exception $e) {
            \Log::error('Laporan Error: ' . $e->getMessage());
            \Filament\Notifications\Notification::make()->title('Gagal memuat laporan')->body('Data tidak ditemukan atau terjadi kesalahan.')->danger()->send();
        }
    }

    public function resetFilter()
    {
        $jenis = $this->filterData['jenis_laporan'] ?? null;
        $this->form->fill(['jenis_laporan' => $jenis]);
        $this->isDataLoaded = false;
        $this->reportData = [];
    }

    // Export Excel action redirect
    public function exportExcel()
    {
        if (!$this->isDataLoaded) return;
        
        $params = http_build_query($this->filterData);
        return redirect('/laporan/export-excel?' . $params);
    }

    // Cetak PDF/Print action redirect
    public function cetakLaporan()
    {
        if (!$this->isDataLoaded) return;
        
        $params = http_build_query($this->filterData);
        // Dispatch browser event to open in new tab
        $this->dispatch('open-print-window', url: '/laporan/cetak?' . $params);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (($this->filterData['jenis_laporan'] ?? '') !== 'supplier' || !$this->isDataLoaded) {
                    return Supplier::query()->whereNull('id');
                }
                
                $query = Supplier::query()->with(['products', 'productGroup']);
                
                if (!empty($this->filterData['kategori'])) {
                    $query->where('kategori', $this->filterData['kategori']);
                }
                if (!empty($this->filterData['status'])) {
                    $query->where('status_kerja_sama', $this->filterData['status']);
                }
                
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('kode_supplier')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_supplier')
                    ->label('Supplier')
                    ->description(fn (Supplier $record): string => $record->nomor_telepon ?? '')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Jenis Supplier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material' => 'info',
                        'Packaging Material' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('productGroup.nama_kelompok_produk')
                    ->label('Kelompok Produk')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('products.nama_produk')
                    ->label('Produk Detail')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('masa_kerja_sama')
                    ->label('Masa Kerja Sama')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state . ' Tahun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_kerja_sama')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_table')
                    ->label('Kategori Supplier')
                    ->attribute('kategori')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ]),
                Tables\Filters\SelectFilter::make('status_table')
                    ->label('Status Supplier')
                    ->attribute('status_kerja_sama')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Nonaktif' => 'Nonaktif',
                    ]),
                Tables\Filters\SelectFilter::make('kelompok_produk')
                    ->label('Kelompok Produk')
                    ->relationship('productGroup', 'nama_kelompok_produk'),
                Tables\Filters\SelectFilter::make('masa_kerja')
                    ->label('Masa Kerja Sama')
                    ->form([
                        \Filament\Forms\Components\Select::make('masa')
                            ->options([
                                '1' => 'Kurang dari 1 tahun',
                                '1_5' => '1–5 tahun',
                                '6_10' => '6–10 tahun',
                                '10_plus' => 'Lebih dari 10 tahun',
                            ])
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['masa'])) return $query;
                        return match ($data['masa']) {
                            '1' => $query->where('masa_kerja_sama', '<', 1),
                            '1_5' => $query->whereBetween('masa_kerja_sama', [1, 5]),
                            '6_10' => $query->whereBetween('masa_kerja_sama', [6, 10]),
                            '10_plus' => $query->where('masa_kerja_sama', '>', 10),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Supplier $record): string => \App\Filament\Resources\SupplierResource::getUrl('edit', ['record' => $record->id]))
            ])
            ->emptyStateHeading('Data supplier tidak ditemukan')
            ->emptyStateDescription('Ubah filter laporan atau kata pencarian untuk menampilkan data supplier.')
            ->emptyStateActions([
                Tables\Actions\Action::make('reset')
                    ->label('Reset Filter')
                    ->action(fn () => $this->resetTable()),
            ])
            ->paginated([10, 25, 50, 'all']);
    }

    public function resetTable()
    {
        $this->resetTableFilters();
        $this->resetTableSearch();
    }
}