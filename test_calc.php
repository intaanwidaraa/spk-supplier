<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $p = App\Models\EvaluationPeriod::first();
    $g = App\Models\ProductGroup::first();
    if ($p && $g) {
        $s = app(App\Services\PerformanceCalculationService::class);
        echo 'Result: ' . $s->calculateForGroup($p, $g);
    } else {
        echo 'No data to test';
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine();
}
