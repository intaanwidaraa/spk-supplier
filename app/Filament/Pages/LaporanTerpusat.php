<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class LaporanTerpusat extends Page
{
    protected static ?string $navigationIcon =
        'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel =
        'Laporan Terpusat';

    protected static ?string $navigationGroup =
        'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title =
        'Laporan Terpusat';

    protected static ?string $slug =
        'laporan-terpusat';

    protected static string $view =
        'filament.pages.laporan-terpusat';

    public function getSubheading(): ?string
    {
        return 'Lihat dan kelola rangkuman hasil evaluasi serta pemeringkatan supplier.';
    }
}