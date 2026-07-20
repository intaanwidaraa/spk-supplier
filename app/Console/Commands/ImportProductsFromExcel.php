<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportProductsFromExcel extends Command
{
    protected $signature   = 'import:products-from-excel
                                {--file= : Path to the Excel file (default: storage/app/imports/PURCHASE RECEIVE MAS 2025.xlsx)}
                                {--sheet=Supplier RM&PM : Sheet name to read}
                                {--dry-run : Run without saving to database}';

    protected $description = 'Import product groups, product details, and supplier-product relations from Excel sheet Supplier RM&PM';

    private array $warnings = [];
    private int $groupCreated   = 0;
    private int $productCreated = 0;
    private int $pivotCreated   = 0;

    public function handle(): int
    {
        $filePath = $this->option('file')
            ?? storage_path('app/imports/PURCHASE RECEIVE MAS 2025.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            $this->line("Letakkan file Excel di: " . storage_path('app/imports/'));
            return self::FAILURE;
        }

        // Require PhpSpreadsheet (via maatwebsite/excel)
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $this->error("Package maatwebsite/excel atau phpoffice/phpspreadsheet belum terpasang.");
            $this->line("Jalankan: php composer.phar require maatwebsite/excel");
            return self::FAILURE;
        }

        $this->info("📂 Membaca file: {$filePath}");

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheetName   = $this->option('sheet');
            $sheet       = $spreadsheet->getSheetByName($sheetName);

            if (!$sheet) {
                // Try to find a similar sheet name
                $sheetNames = $spreadsheet->getSheetNames();
                $this->error("Sheet '{$sheetName}' tidak ditemukan.");
                $this->line("Sheet yang tersedia: " . implode(', ', $sheetNames));
                return self::FAILURE;
            }

            $this->info("✅ Sheet ditemukan: {$sheetName}");
            $rows = $sheet->toArray(null, true, true, true);

        } catch (\Exception $e) {
            $this->error("Gagal membaca file: " . $e->getMessage());
            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("⚠️  DRY RUN MODE — Tidak ada data yang akan disimpan.");
        }

        $this->processRows($rows, $isDryRun);
        $this->printSummary($isDryRun);

        return self::SUCCESS;
    }

    /**
     * Process all rows from the sheet.
     * Format yang diasumsikan dari sheet Supplier RM&PM:
     * Col A: Kategori Produk (Raw Material / Packaging Material) — merged header
     * Col B: Nama Kelompok Produk — merged header
     * Col C: Nama Produk Detail
     * Col D+: Nama Supplier (satu kolom per supplier)
     */
    private function processRows(array $rows, bool $isDryRun): void
    {
        $currentCategory    = null;
        $currentGroupName   = null;
        $supplierColumns    = []; // [colLetter => supplierName]
        $headerRowDetected  = false;
        $dataStarted        = false;

        $this->info("📊 Memproses baris data...");
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $rowIndex => $row) {
            $bar->advance();

            // Skip completely empty rows
            if ($this->isEmptyRow($row)) {
                continue;
            }

            // Collect all non-empty cell values
            $cells = array_filter(array_map('trim', $row), fn ($v) => $v !== '' && $v !== null);

            if (empty($cells)) continue;

            $firstCell = trim(reset($cells));
            $firstCol  = array_key_first($cells);

            // Detect category header rows
            if (in_array(Str::upper($firstCell), ['RAW MATERIAL', 'PACKAGING MATERIAL',
                'RAW MATERIALS', 'PACKAGING MATERIALS'])) {
                $currentCategory = str_contains(Str::upper($firstCell), 'RAW')
                    ? 'Raw Material' : 'Packaging Material';
                $headerRowDetected = false;
                continue;
            }

            // Detect the header row that contains supplier names
            // Typically: "No." | "Nama Kelompok" | "Nama Produk" | Supplier1 | Supplier2 ...
            if (!$headerRowDetected && $this->isSupplierHeaderRow($cells)) {
                $supplierColumns = $this->extractSupplierColumns($cells);
                $headerRowDetected = true;
                $dataStarted       = true;
                continue;
            }

            if (!$dataStarted || !$currentCategory) {
                continue;
            }

            // Skip rows that look like totals or separators
            if ($this->isTotalOrSeparatorRow($firstCell)) {
                continue;
            }

            // Detect group name (usually in col B or a prominent column, non-numeric)
            // Product detail row (col C or D onwards)
            $this->processDataRow(
                $row,
                $cells,
                $currentCategory,
                $currentGroupName,
                $supplierColumns,
                $isDryRun,
                $currentGroupName  // will be updated in-method
            );

            // Try to update current group based on row structure
            // If col B has a non-empty value and it's not a product name, treat it as a group
            $colB = isset($row['B']) ? trim((string) $row['B']) : '';
            $colC = isset($row['C']) ? trim((string) $row['C']) : '';

            if (!empty($colB) && empty($colC)) {
                // Col B has value but C is empty → likely a group header row
                if (!$this->isNumeric($colB) && !$this->isJunkText($colB)) {
                    $currentGroupName = $colB;
                }
            } elseif (!empty($colB)) {
                $currentGroupName = $colB;
            }
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function processDataRow(
        array $row,
        array $cells,
        string $category,
        ?string &$currentGroupName,
        array $supplierColumns,
        bool $isDryRun,
        ?string $existingGroupName
    ): void {
        $colA = isset($row['A']) ? trim((string) $row['A']) : '';
        $colB = isset($row['B']) ? trim((string) $row['B']) : '';
        $colC = isset($row['C']) ? trim((string) $row['C']) : '';

        // Update group name if col B has value
        if (!empty($colB) && !$this->isNumeric($colB) && !$this->isJunkText($colB)) {
            $currentGroupName = $colB;
        }

        // Product detail is in col C (or D if structure differs)
        $productName = $colC;

        // If col C is empty, check if colB is a product name under existing group
        if (empty($productName) && !empty($colB) && $currentGroupName !== $colB) {
            $productName = $colB;
        }

        if (empty($productName) || $this->isJunkText($productName) || $this->isNumeric($productName)) {
            return;
        }

        if (empty($currentGroupName)) {
            $this->warnings[] = "Baris tanpa kelompok produk: '{$productName}'";
            return;
        }

        if (!$isDryRun) {
            // Upsert ProductGroup
            $group = ProductGroup::firstOrCreate(
                [
                    'nama_kelompok_produk' => $currentGroupName,
                    'kategori_produk'      => $category,
                ],
                [
                    'kode_kelompok_produk' => ProductGroup::generateNextCode(),
                    'status'               => true,
                ]
            );

            if ($group->wasRecentlyCreated) {
                $this->groupCreated++;
            }

            // Upsert Product detail
            $product = Product::firstOrCreate(
                [
                    'nama_produk'      => $productName,
                    'product_group_id' => $group->id,
                ],
                [
                    'kode_produk'    => Product::generateNextCode(),
                    'kategori_produk'=> $category,
                    'status'         => true,
                ]
            );

            if ($product->wasRecentlyCreated) {
                $this->productCreated++;
            }

            // Link suppliers
            foreach ($supplierColumns as $col => $supplierName) {
                $cellValue = isset($row[$col]) ? trim((string) $row[$col]) : '';
                if (empty($cellValue) || in_array(strtolower($cellValue), ['', '0', '-', 'x'])) {
                    continue;
                }

                $supplier = Supplier::whereRaw('LOWER(nama_supplier) LIKE ?', [
                    '%' . strtolower($supplierName) . '%'
                ])->first();

                if (!$supplier) {
                    $this->warnings[] = "Supplier tidak ditemukan di database: '{$supplierName}' (produk: {$productName})";
                    continue;
                }

                $exists = DB::table('supplier_products')
                    ->where('supplier_id', $supplier->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if (!$exists) {
                    DB::table('supplier_products')->insert([
                        'supplier_id' => $supplier->id,
                        'product_id'  => $product->id,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    $this->pivotCreated++;
                }
            }
        }
    }

    private function extractSupplierColumns(array $cells): array
    {
        $supplierCols = [];
        $skipKeywords = ['no', 'nama kelompok', 'nama produk', 'kelompok', 'produk', 'satuan', 'keterangan', '#'];

        foreach ($cells as $col => $value) {
            $val = strtolower(trim((string) $value));
            if (empty($val)) continue;
            $isSkip = false;
            foreach ($skipKeywords as $kw) {
                if (str_contains($val, $kw)) {
                    $isSkip = true;
                    break;
                }
            }
            if (!$isSkip && $col >= 'D') {
                $supplierCols[$col] = trim((string) $value);
            }
        }

        return $supplierCols;
    }

    private function isSupplierHeaderRow(array $cells): bool
    {
        $values = array_map('strtolower', array_map('trim', $cells));
        return count(array_filter($values, fn ($v) => str_contains($v, 'produk') || str_contains($v, 'kelompok'))) > 0;
    }

    private function isEmptyRow(array $row): bool
    {
        return empty(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== ''));
    }

    private function isNumeric(string $val): bool
    {
        return is_numeric(trim($val));
    }

    private function isJunkText(string $val): bool
    {
        $lower = strtolower(trim($val));
        $junk = ['pabrik', 'cabang', 'group', 'sub total', 'total', 'grand total', 'keterangan'];
        foreach ($junk as $j) {
            if (str_contains($lower, $j)) return true;
        }
        return false;
    }

    private function isTotalOrSeparatorRow(string $firstCell): bool
    {
        $lower = strtolower(trim($firstCell));
        return str_contains($lower, 'total') || str_contains($lower, 'sub total') || $lower === '';
    }

    private function printSummary(bool $isDryRun): void
    {
        $this->newLine();
        $this->info("=== RINGKASAN IMPORT ===");

        if ($isDryRun) {
            $this->warn("(DRY RUN — tidak ada yang disimpan)");
        }

        $this->table(
            ['Item', 'Jumlah'],
            [
                ['Kelompok Produk Baru', $this->groupCreated],
                ['Produk Detail Baru', $this->productCreated],
                ['Relasi Supplier-Produk Baru', $this->pivotCreated],
                ['Peringatan', count($this->warnings)],
            ]
        );

        if (!empty($this->warnings)) {
            $this->newLine();
            $this->warn("⚠️  PERINGATAN (" . count($this->warnings) . " item):");
            foreach (array_slice($this->warnings, 0, 30) as $warning) {
                $this->line("  - {$warning}");
            }
            if (count($this->warnings) > 30) {
                $this->line("  ... dan " . (count($this->warnings) - 30) . " peringatan lainnya.");
            }

            // Save warnings to log file
            $logPath = storage_path('logs/import-products-warnings.log');
            file_put_contents($logPath, implode("\n", $this->warnings));
            $this->line("📝 Log peringatan disimpan ke: {$logPath}");
        }
    }
}
