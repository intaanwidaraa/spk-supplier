<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$evaluationPeriod = App\Models\EvaluationPeriod::query()->latest()->first();

$suppliersQuery = App\Models\Supplier::query();
$suppliersQuery->where('nama_supplier', 'CV ALIM JAYA');
$suppliers = $suppliersQuery->get();
$validSupplierIds = $suppliers->pluck('id')->toArray();

$purchaseHistoriesQuery = App\Models\PurchaseHistory::whereIn('supplier_id', $validSupplierIds)
    ->whereYear('tanggal_pembelian', $evaluationPeriod->year);
$allPurchaseHistories = $purchaseHistoriesQuery->get();
var_dump("Histories count: " . $allPurchaseHistories->count());

$svc = app(App\Services\SupplierPerformanceCalculationService::class);
$criteriaList = App\Models\Criteria::all()->keyBy('kode_kriteria');

$productPriceGroups = $allPurchaseHistories->filter(function($h){ return $h->qty_pembelian>0 && $h->harga_satuan>0; })->groupBy(function ($item) {
    $identity = $item->kode_produk ?: $item->nama_produk;
    return trim(strtoupper($identity)) . '|' . trim(strtoupper($item->satuan));
});
$medianPrices = [];
// ... fake median logic, not relevant if single source

foreach ($suppliers as $supplier) {
    $supplierHistories = $allPurchaseHistories->where('supplier_id', $supplier->id);
    
    $ref1 = new ReflectionMethod($svc, 'calculateC1'); $ref1->setAccessible(true);
    $c1 = $ref1->invoke($svc, $supplierHistories, $criteriaList->get('C1'));
    var_dump("C1: " . ($c1 ? 'OK' : 'NULL'));
    
    $ref2 = new ReflectionMethod($svc, 'calculateC2'); $ref2->setAccessible(true);
    $c2 = $ref2->invoke($svc, $supplierHistories, $medianPrices, $criteriaList->get('C2'));
    var_dump("C2: " . ($c2 ? 'OK' : 'NULL'));

    $ref3 = new ReflectionMethod($svc, 'calculateC3'); $ref3->setAccessible(true);
    $c3 = $ref3->invoke($svc, $supplier, $criteriaList->get('C3'));
    var_dump("C3: " . ($c3 ? 'OK' : 'NULL'));
    
    $ref4 = new ReflectionMethod($svc, 'calculateC4'); $ref4->setAccessible(true);
    $c4 = $ref4->invoke($svc, $supplierHistories, $criteriaList->get('C4'));
    var_dump("C4: " . ($c4 ? 'OK' : 'NULL'));
    
    $ref5 = new ReflectionMethod($svc, 'calculateC5'); $ref5->setAccessible(true);
    $c5 = $ref5->invoke($svc, $supplierHistories, $criteriaList->get('C5'));
    var_dump("C5: " . ($c5 ? 'OK' : 'NULL'));
}
