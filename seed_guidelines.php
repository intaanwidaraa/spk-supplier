<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$criteria = DB::table('criteria')->get();

foreach ($criteria as $c) {
    for ($i = 1; $i <= 5; $i++) {
        DB::table('criterion_score_guidelines')->insertOrIgnore([
            'criterion_id' => $c->id,
            'score' => $i,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
echo "Seeded score guidelines for existing criteria.\n";
