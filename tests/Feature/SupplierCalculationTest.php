<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Supplier;
use App\Models\PurchaseHistory;
use App\Models\EvaluationPeriod;
use App\Models\Criteria;
use App\Services\SupplierPerformanceCalculationService;

class SupplierCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed basic criteria
        Criteria::create(['kode_kriteria' => 'C1', 'nama_kriteria' => 'Kualitas Produk']);
        Criteria::create(['kode_kriteria' => 'C2', 'nama_kriteria' => 'Harga']);
        Criteria::create(['kode_kriteria' => 'C3', 'nama_kriteria' => 'Lama Kerja Sama']);
        Criteria::create(['kode_kriteria' => 'C4', 'nama_kriteria' => 'Ketepatan Kuantitas']);
        Criteria::create(['kode_kriteria' => 'C5', 'nama_kriteria' => 'Ketepatan Waktu Pengiriman']);

        EvaluationPeriod::create([
            'evaluation_code' => 'EV-TEST-2025',
            'name' => 'Penilaian Otomatis Supplier 2025',
            'year' => 2025,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'product_category' => 'Raw Material',
            'status' => 'Aktif',
            'description' => 'Test',
        ]);
    }

    public function test_calculate_c1_repeat_product_rate()
    {
        $supplier = Supplier::factory()->create(['nama_supplier' => 'CV TESTER']);
        
        // Create 2 POs for product A, 1 PO for product B. Total products = 2. Repeated = 1. RPR = 50%.
        PurchaseHistory::factory()->create(['supplier_id' => $supplier->id, 'nama_produk' => 'A', 'satuan' => 'KG', 'nomor_po' => 'PO1', 'tanggal_pembelian' => '2025-01-01', 'qty_pembelian' => 10]);
        PurchaseHistory::factory()->create(['supplier_id' => $supplier->id, 'nama_produk' => 'A', 'satuan' => 'KG', 'nomor_po' => 'PO2', 'tanggal_pembelian' => '2025-01-02', 'qty_pembelian' => 10]);
        PurchaseHistory::factory()->create(['supplier_id' => $supplier->id, 'nama_produk' => 'B', 'satuan' => 'KG', 'nomor_po' => 'PO3', 'tanggal_pembelian' => '2025-01-03', 'qty_pembelian' => 10]);

        $svc = app(SupplierPerformanceCalculationService::class);
        $svc->calculateAndSyncAll();

        $assessment = $supplier->performanceAssessments()->first();
        $this->assertNotNull($assessment);
        
        // 50% => score 3 ( >40 and <=60 )
        $this->assertEquals(3, $assessment->c1_score);
    }

    public function test_calculate_c3_masa_kerja_sama()
    {
        $supplier = Supplier::factory()->create(['nama_supplier' => 'CV TESTER', 'masa_kerja_sama' => 6]);
        
        PurchaseHistory::factory()->create(['supplier_id' => $supplier->id, 'nama_produk' => 'A', 'satuan' => 'KG', 'nomor_po' => 'PO1', 'tanggal_pembelian' => '2025-01-01', 'qty_pembelian' => 10, 'qty_diterima' => 10, 'harga_satuan' => 100]);

        $svc = app(SupplierPerformanceCalculationService::class);
        $svc->calculateAndSyncAll();

        $assessment = $supplier->performanceAssessments()->first();
        
        // 6 years => score 3 (>=5 and <7)
        $this->assertEquals(3, $assessment->c3_score);
    }

    public function test_calculate_c4_fulfillment_rate()
    {
        $supplier = Supplier::factory()->create(['nama_supplier' => 'CV TESTER', 'masa_kerja_sama' => 2]);
        
        // Qty Beli = 100, Qty Terima = 85. FR = 85% => score 2 (>=80 and <90)
        PurchaseHistory::factory()->create(['supplier_id' => $supplier->id, 'nama_produk' => 'A', 'satuan' => 'KG', 'nomor_po' => 'PO1', 'tanggal_pembelian' => '2025-01-01', 'qty_pembelian' => 100, 'qty_diterima' => 85, 'harga_satuan' => 100]);

        $svc = app(SupplierPerformanceCalculationService::class);
        $svc->calculateAndSyncAll();

        $assessment = $supplier->performanceAssessments()->first();
        $this->assertEquals(2, $assessment->c4_score);
    }

    public function test_calculate_c5_lead_time()
    {
        $supplier = Supplier::factory()->create(['nama_supplier' => 'CV TESTER', 'masa_kerja_sama' => 2]);
        
        PurchaseHistory::factory()->create(['supplier_id' => $supplier->id, 'nama_produk' => 'A', 'satuan' => 'KG', 'nomor_po' => 'PO1', 'tanggal_pembelian' => '2025-01-01', 'tanggal_penerimaan' => '2025-01-10', 'qty_pembelian' => 10, 'qty_diterima' => 10, 'harga_satuan' => 100]); // 9 days

        $svc = app(SupplierPerformanceCalculationService::class);
        $svc->calculateAndSyncAll();

        $assessment = $supplier->performanceAssessments()->first();
        
        // 9 days => score 4 (>8.8 and <=10.6)
        $this->assertEquals(4, $assessment->c5_score);
    }
}
