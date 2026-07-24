<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public static function getSupplierReportQuery(array $filters): Builder
    {
        $query = Supplier::query()->with(['productGroup', 'products']);

        // Filter Utama Laporan (dari LaporanTerpusat $filterData)
        if (!empty($filters['kategori'])) {
            $query->where('kategori', $filters['kategori']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status_kerja_sama', $filters['status']);
        }

        if (!empty($filters['product_group_id'])) {
            $query->whereHas('products', function ($q) use ($filters) {
                $q->where('product_group_id', $filters['product_group_id']);
            });
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('products', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        return $query;
    }
}
