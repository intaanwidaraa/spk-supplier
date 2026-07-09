<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PerhitunganSupplier extends Page
{
    protected static ?string $navigationIcon =
        'heroicon-o-calculator';

    protected static ?string $navigationLabel =
        'Perhitungan Supplier';

    protected static ?string $navigationGroup =
        'Proses Evaluasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title =
        'Perhitungan Supplier';

    protected static ?string $slug =
        'perhitungan-supplier';

    protected static string $view =
        'filament.pages.perhitungan-supplier';

    public function getSubheading(): ?string
    {
        return 'Proses perhitungan dan pemeringkatan supplier berdasarkan jenis supplier.';
    }
}