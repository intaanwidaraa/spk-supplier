<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplierPerformanceAssessment;
use App\Models\Supplier;

class CompareSupplierScoresCommand extends Command
{
    protected $signature = 'compare:supplier-scores';
    protected $description = 'Bandingkan hasil perhitungan database dengan expected data spreadsheet';

    protected $expectedData = [
        'CV ALIM JAYA' => [
            'C1' => 3, 'C2' => 5, 'C3' => 3, 'C4' => 4, 'C5' => 3, 'Total' => 3.60,
        ],
        'PT CENTURY MITRA SUKSES SEJATI' => [
            'C1' => 5, 'C2' => 1, 'C3' => 5, 'C4' => 5, 'C5' => 3, 'Total' => 3.80,
        ],
        'PT GALIC BINA MADA' => [
            'C1' => 5, 'C2' => 1, 'C3' => 5, 'C4' => 5, 'C5' => 3, 'Total' => 3.80,
        ],
        'PT QUANTUM CEMERLANG' => [
            'C1' => 3, 'C2' => 2, 'C3' => 5, 'C4' => 5, 'C5' => 5, 'Total' => 4.00,
        ],
        'CV TRINA JAYA' => [
            'C1' => 3, 'C2' => 5, 'C3' => 2, 'C4' => 3, 'C5' => 4, 'Total' => 3.40,
        ],
    ];

    public function handle()
    {
        $this->info("Membandingkan hasil Assessment dengan data expected...");

        $headers = ['Nama Supplier', 'C1 (Exp|Act)', 'C2 (Exp|Act)', 'C3 (Exp|Act)', 'C4 (Exp|Act)', 'C5 (Exp|Act)', 'Total (Exp|Act)', 'Status'];
        $rows = [];

        foreach ($this->expectedData as $name => $exp) {
            $supplier = Supplier::where('nama_supplier', $name)->first();
            if (!$supplier) {
                $this->error("Supplier $name tidak ditemukan!");
                continue;
            }

            $assessment = SupplierPerformanceAssessment::where('supplier_id', $supplier->id)->orderByDesc('id')->first();
            if (!$assessment) {
                $rows[] = [
                    $name,
                    "{$exp['C1']} | -",
                    "{$exp['C2']} | -",
                    "{$exp['C3']} | -",
                    "{$exp['C4']} | -",
                    "{$exp['C5']} | -",
                    "{$exp['Total']} | -",
                    'MISSING DB'
                ];
                continue;
            }

            $match = (
                $assessment->c1_score == $exp['C1'] &&
                $assessment->c2_score == $exp['C2'] &&
                $assessment->c3_score == $exp['C3'] &&
                $assessment->c4_score == $exp['C4'] &&
                $assessment->c5_score == $exp['C5'] &&
                number_format($assessment->total_score, 2) == number_format($exp['Total'], 2)
            );

            $rows[] = [
                $name,
                "{$exp['C1']} | {$assessment->c1_score}",
                "{$exp['C2']} | {$assessment->c2_score}",
                "{$exp['C3']} | {$assessment->c3_score}",
                "{$exp['C4']} | {$assessment->c4_score}",
                "{$exp['C5']} | {$assessment->c5_score}",
                number_format($exp['Total'], 2) . " | " . number_format($assessment->total_score, 2),
                $match ? '<fg=green>MATCH</>' : '<fg=red>DIFF</>'
            ];
        }

        $this->table($headers, $rows);

        // Print general stats
        $totalAssessments = SupplierPerformanceAssessment::count();
        $totalScoresCorrect = SupplierPerformanceAssessment::whereNotNull('c1_score')
            ->whereNotNull('c2_score')
            ->whereNotNull('c3_score')
            ->whereNotNull('c4_score')
            ->whereNotNull('c5_score')
            ->count();
        $this->info("Total Assessment di DB: $totalAssessments");
        $this->info("Total Assessment yang lengkap C1-C5: $totalScoresCorrect");

        return 0;
    }
}
