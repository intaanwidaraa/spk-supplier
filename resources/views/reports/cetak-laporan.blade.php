<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $tglCetak = \Carbon\Carbon::now()->format('Y-m-d');
        $namaFile = 'Laporan_Data_Supplier';
        if (!empty($filterData['kategori'])) {
            $namaFile .= '_' . str_replace(' ', '', $filterData['kategori']);
        }
        $namaFile .= '_' . $tglCetak;
    @endphp
    <title>{{ $namaFile }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        
        .header-table { width: 100%; border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 20px; }
        .header-table td { border: none; padding: 0; }
        .header-title { font-size: 16pt; font-weight: bold; margin: 0; letter-spacing: 1px; }
        .header-address { font-size: 9pt; margin: 5px 0 0 0; color: #444; }
        
        .report-title { text-align: center; font-size: 14pt; font-weight: bold; margin: 0 0 20px 0; text-transform: uppercase; }
        
        .info-table { width: 100%; border: none; font-size: 9pt; margin-bottom: 15px; }
        .info-table td { border: none; padding: 3px 0; vertical-align: top; }
        
        .summary-box { border: 1px solid #ddd; padding: 10px 15px; margin-bottom: 20px; background-color: #fcfcfc; font-size: 9pt; }
        .summary-box span { margin-right: 20px; }
        .summary-box strong { color: #222; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9pt; }
        table.data-table th, table.data-table td { border: 1px solid #aaa; padding: 6px; text-align: left; vertical-align: top; }
        table.data-table th { background-color: #eee; font-weight: bold; text-align: center; }
        table.data-table td.center { text-align: center; }
        table.data-table td.right { text-align: right; }
        table.data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #777; border-top: 1px solid #ddd; padding-top: 5px; }
        
        .section-title { font-size: 11pt; font-weight: bold; margin-bottom: 10px; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            @if($jenis == 'supplier')
                @page { size: A4 landscape; margin: 15mm; }
            @else
                @page { size: A4 portrait; margin: 15mm; }
            @endif
            table.data-table { page-break-inside: auto; }
            table.data-table tr { page-break-inside: avoid; page-break-after: auto; }
            table.data-table thead { display: table-header-group; }
            table.data-table tfoot { display: table-footer-group; }
            .footer { position: fixed; bottom: 0; }
        }
        
        .btn-print { display: inline-block; padding: 10px 20px; background: #0284c7; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 20px; border: none; cursor: pointer; }
        
        ul.product-list { margin: 0; padding-left: 15px; }
        ul.product-list li { margin-bottom: 2px; }
    </style>
</head>
<body onload="window.print()">
    
    <div class="no-print" style="text-align: right;">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Sekarang</button>
    </div>

    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                <img src="/logo.png" onerror="this.style.display='none'" style="max-height: 70px;" alt="Logo" />
            </td>
            <td style="width: 70%; text-align: center;">
                <h1 class="header-title">PT MAKMUR ARTHA SEJAHTERA</h1>
                <p class="header-address">Jl. Ki Ageng Tapa Blok Nambo No. 168,<br>Astapada, Kec. Tengah Tani, Kabupaten Cirebon, Jawa Barat 45153</p>
            </td>
            <td style="width: 15%;"></td>
        </tr>
    </table>

    <h2 class="report-title">{{ $judul }}</h2>

    <table class="info-table">
        <tr>
            <td style="width: 15%;">Jenis Laporan</td>
            <td style="width: 35%;">: {{ $judul }}</td>
            <td style="width: 15%;">Tanggal Cetak</td>
            <td style="width: 35%;">: {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td>Kategori Supplier</td>
            <td>: {{ $filterData['kategori'] ?? 'Semua' }}</td>
            <td>Waktu Cetak</td>
            <td>: {{ date('H:i') }} WIB</td>
        </tr>
        @if($jenis == 'supplier' || $jenis == 'penilaian' || $jenis == 'historis')
        <tr>
            <td>Kelompok Produk</td>
            <td>: {{ !empty($filterData['product_group_id']) ? \App\Models\ProductGroup::find($filterData['product_group_id'])->nama_kelompok_produk ?? 'Semua' : 'Semua' }}</td>
            <td>Dicetak Oleh</td>
            <td>: {{ $user }}</td>
        </tr>
        <tr>
            <td>Status / Filter Lain</td>
            <td>: {{ $filterData['status'] ?? 'Semua' }}</td>
            <td>Total Data</td>
            <td>: {{ collect($reportData[$jenis] ?? [])->count() }}</td>
        </tr>
        @else
        <tr>
            <td>Filter Lainnya</td>
            <td>: Berdasarkan Parameter Tersimpan</td>
            <td>Dicetak Oleh</td>
            <td>: {{ $user }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>Total Data</td>
            <td>: {{ collect($reportData[$jenis] ?? [])->count() }}</td>
        </tr>
        @endif
    </table>

    @if($jenis == 'supplier')
        @php 
            $data = collect($reportData['supplier']); 
            $total = $data->count();
            $rm = $data->where('kategori', 'Raw Material')->count();
            $pm = $data->where('kategori', 'Packaging Material')->count();
            $aktif = $data->where('status_kerja_sama', 'Aktif')->count();
            $nonaktif = $total - $aktif;
        @endphp
        <div class="summary-box">
            <span><strong>Total Supplier:</strong> {{ $total }}</span>
            <span><strong>Raw Material:</strong> {{ $rm }}</span>
            <span><strong>Packaging Material:</strong> {{ $pm }}</span>
            <span><strong>Aktif:</strong> {{ $aktif }}</span>
            <span><strong>Nonaktif:</strong> {{ $nonaktif }}</span>
        </div>
    @endif

    @if(collect($reportData[$jenis] ?? [])->isEmpty() && !in_array($jenis, ['evaluasi', 'ranking']))
        <div style="text-align: center; padding: 40px; border: 1px dashed #ccc; color: #777;">
            Tidak terdapat data sesuai filter yang dipilih.
        </div>
    @else
        {{-- KONTEN TABEL --}}
        @if($jenis == 'evaluasi' || $jenis == 'ranking')
            @php $calc = $reportData['calculation']; $ranks = $reportData['rankings']; @endphp

            @if($jenis == 'evaluasi')
                <div class="section-title">A. Hasil Pembobotan Kriteria (RECA)</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Rank</th><th>Kode</th><th>Kriteria</th><th>Atribut</th><th>Bobot</th><th>Persentase</th></tr>
                    </thead>
                    <tbody>
                        @foreach($calc->recaDetails as $d)
                            <tr>
                                <td class="center">{{ $d->contribution_rank }}</td>
                                <td class="center">{{ $d->criteria_code }}</td>
                                <td>{{ $d->criteria_name }}</td>
                                <td class="center">{{ $d->attribute }}</td>
                                <td class="right">{{ number_format($d->weight, 4) }}</td>
                                <td class="right">{{ number_format($d->weight_percentage, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="section-title">B. Hasil Perangkingan (MAUT)</div>
            @endif

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th><th>Kode</th><th>Supplier</th>
                        @if($jenis == 'evaluasi') <th>C1</th><th>C2</th><th>C3</th><th>C4</th><th>C5</th> @endif
                        <th>Skor Utilitas</th><th>Status Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranks as $r)
                        @php $supSnap = $calc->selectedSuppliers->where('supplier_id', $r->supplier_id)->first(); @endphp
                        <tr>
                            <td class="center" style="font-weight: bold;">{{ $r->rank }}</td>
                            <td class="center">{{ $supSnap->supplier_code ?? '-' }}</td>
                            <td>{{ $r->supplier_name }}</td>
                            @if($jenis == 'evaluasi')
                                <td class="center">{{ $supSnap->c1_score ?? '-' }}</td>
                                <td class="center">{{ $supSnap->c2_score ?? '-' }}</td>
                                <td class="center">{{ $supSnap->c3_score ?? '-' }}</td>
                                <td class="center">{{ $supSnap->c4_score ?? '-' }}</td>
                                <td class="center">{{ $supSnap->c5_score ?? '-' }}</td>
                            @endif
                            <td class="right" style="font-weight: bold;">{{ number_format($r->final_score, 4) }}</td>
                            <td class="center">{{ $r->rank == 1 ? 'Direkomendasikan' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if($jenis == 'evaluasi' && count($ranks) > 0)
            <div class="section-title" style="border:none;">Kesimpulan</div>
            <p style="text-align: justify; font-size: 10pt;">
                Berdasarkan hasil perhitungan menggunakan metode kombinasi RECA dan MAUT, supplier yang paling direkomendasikan adalah <b>{{ $ranks->first()->supplier_name }}</b> dengan skor utilitas akhir sebesar <b>{{ number_format($ranks->first()->final_score, 4) }}</b>. Kriteria yang memiliki bobot (pengaruh) terbesar dalam evaluasi ini adalah <b>{{ $calc->recaDetails->first()->criteria_name }} ({{ number_format($calc->recaDetails->first()->weight_percentage, 2) }}%)</b>.
            </p>
            @endif

        @elseif($jenis == 'pembobotan')
            @php $calc = $reportData['calculation']; @endphp
            <table class="data-table">
                <thead>
                    <tr><th>Rank</th><th>Kode</th><th>Kriteria</th><th>Atribut</th><th>Std Val</th><th>Var Val</th><th>Dev Val</th><th>Bobot</th><th>%</th></tr>
                </thead>
                <tbody>
                    @foreach($calc->recaDetails as $d)
                        <tr>
                            <td class="center">{{ $d->contribution_rank }}</td><td class="center">{{ $d->criteria_code }}</td><td>{{ $d->criteria_name }}</td><td class="center">{{ $d->attribute }}</td>
                            <td class="right">{{ number_format($d->standard_value, 4) }}</td><td class="right">{{ number_format($d->variation_value, 4) }}</td><td class="right">{{ number_format($d->deviation_value, 4) }}</td>
                            <td class="right" style="font-weight: bold;">{{ number_format($d->weight, 4) }}</td><td class="right">{{ number_format($d->weight_percentage, 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($jenis == 'penilaian')
            <table class="data-table">
                <thead>
                    <tr><th>Kode</th><th>Supplier</th><th>C1</th><th>C2</th><th>C3</th><th>C4</th><th>C5</th><th>Total</th><th>Status Data</th></tr>
                </thead>
                <tbody>
                    @foreach($reportData['penilaian'] as $r)
                        <tr>
                            <td class="center">{{ $r['supplier_code'] }}</td><td>{{ $r['supplier_name'] }}</td>
                            <td class="center">{{ $r['c1'] ?: '-' }}</td><td class="center">{{ $r['c2'] ?: '-' }}</td><td class="center">{{ $r['c3'] ?: '-' }}</td><td class="center">{{ $r['c4'] ?: '-' }}</td><td class="center">{{ $r['c5'] ?: '-' }}</td>
                            <td class="center" style="font-weight: bold;">{{ $r['total'] }}</td><td class="center">{{ $r['status_data'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($jenis == 'historis')
            <table class="data-table">
                <thead>
                    <tr><th>No</th><th>PO Date</th><th>Rcv Date</th><th>Supplier</th><th>Produk</th><th>Qty PO</th><th>Qty Rcv</th><th>Hrg Sat.</th><th>Total</th><th>Lead Time</th></tr>
                </thead>
                <tbody>
                    @foreach($reportData['historis'] as $idx => $r)
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td class="center">{{ \Carbon\Carbon::parse($r->tanggal_pembelian)->format('d/m/y') }}</td>
                            <td class="center">{{ $r->tanggal_penerimaan ? \Carbon\Carbon::parse($r->tanggal_penerimaan)->format('d/m/y') : '-' }}</td>
                            <td>{{ $r->supplier->nama_supplier ?? '-' }}</td>
                            <td>{{ $r->nama_produk }}</td>
                            <td class="right">{{ $r->qty_pembelian }}</td>
                            <td class="right">{{ $r->qty_diterima }}</td>
                            <td class="right">{{ number_format($r->harga_satuan, 0, ',', '.') }}</td>
                            <td class="right">{{ number_format($r->total_nilai, 0, ',', '.') }}</td>
                            <td class="center">{{ $r->lead_time_hari ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        @elseif($jenis == 'riwayat')
            <table class="data-table">
                <thead>
                    <tr><th>Kode</th><th>Nama Perhitungan</th><th>Tgl Proses</th><th>Kategori</th><th>Kelompok</th><th>Evaluasi</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($reportData['riwayat'] as $r)
                        <tr>
                            <td class="center">{{ $r->calculation_code }}</td><td>{{ $r->calculation_name }}</td>
                            <td class="center">{{ $r->calculated_at ? $r->calculated_at->format('d/m/Y') : '-' }}</td>
                            <td class="center">{{ $r->supplier_category }}</td>
                            <td>{{ $r->productGroup->nama_kelompok_produk ?? 'Semua' }}</td>
                            <td class="center">{{ $r->total_selected }} Spl.</td><td class="center">{{ $r->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
        @elseif($jenis == 'supplier')
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No.</th>
                        <th style="width: 8%;">Kode</th>
                        <th style="width: 18%;">Nama Supplier</th>
                        <th style="width: 12%;">Jenis Supplier</th>
                        <th style="width: 15%;">Kelompok Produk</th>
                        <th style="width: 25%;">Produk Detail</th>
                        <th style="width: 9%;">Masa Kerja Sama</th>
                        <th style="width: 9%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['supplier'] as $idx => $r)
                        @php
                            $kelompok = $r->products->pluck('productGroup.nama_kelompok_produk')->filter()->unique();
                            $produk = $r->products->pluck('nama_produk')->filter();
                        @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td class="center">{{ $r->kode_supplier }}</td>
                            <td><strong>{{ $r->nama_supplier }}</strong></td>
                            <td class="center">{{ $r->kategori }}</td>
                            <td>
                                @if($kelompok->isNotEmpty())
                                    {!! $kelompok->implode('<br>') !!}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($produk->isNotEmpty())
                                    <ol style="margin: 0; padding-left: 15px;">
                                        @foreach($produk as $p)
                                            <li>{{ $p }}</li>
                                        @endforeach
                                    </ol>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="center">{{ filled($r->masa_kerja_sama) ? $r->masa_kerja_sama . ' Tahun' : '-' }}</td>
                            <td class="center">{{ $r->status_kerja_sama }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <div class="footer">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: left; border: none; padding: 0;">Dicetak melalui Sistem Pendukung Keputusan Pemilihan Supplier (SPK-SUPPLIER)</td>
                <td style="text-align: right; border: none; padding: 0;" class="page-number">Dicetak pada: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
