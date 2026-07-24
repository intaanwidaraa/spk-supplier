<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pg = App\Models\ProductGroup::where('nama_kelompok_produk', 'Toples / Jar Plastik')->first();

$query = App\Models\Supplier::query()
    ->where('kategori', 'Packaging Material');

if ($pg) {
    $productGroupId = $pg->id;
    $query->whereHas('products', function ($q) use ($productGroupId) {
        $q->where('products.product_group_id', $productGroupId);
    });
}
$query->distinct();
$count = $query->count();
$suppliers = $query->pluck('nama_supplier')->toArray();

echo "Count: $count\n";
echo implode("\n", $suppliers) . "\n";
