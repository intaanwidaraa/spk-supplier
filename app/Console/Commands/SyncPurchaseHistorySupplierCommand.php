<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseHistory;
use App\Models\Supplier;

class SyncPurchaseHistorySupplierCommand extends Command
{
    protected $signature = 'sync:supplier-history';
    protected $description = 'Sync supplier_id in purchase_histories based on supplier names';

    protected $aliasMap = [
        'ALIM JAYA, CV' => 'CV ALIM JAYA',
        'ALTINDO MULIA, PT' => 'PT ALTINDO MULIA',
        'LION DIAMOND, PT (LD)' => 'PT LION DIAMOND (LD)',
        'GUNA KEMAS INDAH (GKI), PT' => 'PT GUNA KEMAS INDAH (GKI)',
        'SUMBER FOOD INGREDIENT, PT (SFI)' => 'PT SUMBER FOOD INGREDIENT (SFI)',
        'SENSA MULIA FORTINDO, PT (SMF)' => 'PT SENSA MULIA FORTINDO (SMF)',
        'PEKIRINGAN 103, TOKO' => 'TOKO PEKIRINGAN 103',
        'SUMBER NASINDO REJEKI,PT' => 'PT SUMBER NASINDO REJEKI',
        'TATCO, CV' => 'CV TATCO',
        'TRINA JAYA, CV' => 'CV TRINA JAYA',
        'EVARY PLASTIC, CV' => 'CV EVARY PLASTIC',
        'CENTURY MITRA SUKSES SEJATI, PT' => 'PT CENTURY MITRA SUKSES SEJATI',
        'QUANTUM CEMERLANG, PT' => 'PT QUANTUM CEMERLANG',
        'GALIC BINA MADA, PT' => 'PT GALIC BINA MADA',
    ];

    public function handle()
    {
        $this->info("Memulai sinkronisasi Purchase History...");

        $suppliers = Supplier::all();
        $supplierMap = [];
        
        foreach ($suppliers as $supplier) {
            $normalizedName = $this->normalizeName($supplier->nama_supplier);
            $supplierMap[$normalizedName] = $supplier->id;
        }

        $histories = PurchaseHistory::whereNull('supplier_id')->get();
        $this->info("Ditemukan {$histories->count()} baris history dengan supplier_id NULL.");

        $updatedCount = 0;
        $failedNames = [];

        foreach ($histories as $history) {
            $rawName = trim($history->supplier_name);
            
            if (isset($this->aliasMap[$rawName])) {
                $mappedName = $this->aliasMap[$rawName];
                $normalizedRaw = $this->normalizeName($mappedName);
            } else {
                $normalizedRaw = $this->normalizeName($rawName);
            }

            if (isset($supplierMap[$normalizedRaw])) {
                PurchaseHistory::where('id', $history->id)->update([
                    'supplier_id' => $supplierMap[$normalizedRaw]
                ]);
                $updatedCount++;
            } else {
                $failedNames[$rawName] = true;
            }
        }

        $this->info("Berhasil sinkronisasi {$updatedCount} baris.");

        if (count($failedNames) > 0) {
            $this->warn("Terdapat " . count($failedNames) . " nama supplier unik yang gagal dipetakan:");
            foreach (array_keys($failedNames) as $name) {
                $this->line("- " . $name);
            }
        } else {
            $this->info("Semua nama supplier berhasil dipetakan!");
        }
        
        $suppliersWithoutHistory = Supplier::whereDoesntHave('purchaseHistories')->get();
        if ($suppliersWithoutHistory->isNotEmpty()) {
            $this->info("\nDaftar Master Supplier yang tidak memiliki riwayat pembelian:");
            foreach ($suppliersWithoutHistory as $s) {
                $this->line("- " . $s->nama_supplier);
            }
        }

        return 0;
    }

    public function normalizeName(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/[,.]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);

        $terms = ['PT', 'CV', 'TOKO', 'UD'];
        
        $words = explode(' ', $name);
        if (count($words) > 0 && in_array($words[0], $terms)) {
            array_shift($words);
        }
        if (count($words) > 0 && in_array(end($words), $terms)) {
            array_pop($words);
        }

        return implode(' ', $words);
    }
}
