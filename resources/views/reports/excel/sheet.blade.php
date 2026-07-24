<table>
    @if($type == 'ringkasan')
        <tr><td colspan="2"><b>Ringkasan Evaluasi MAUT</b></td></tr>
        <tr><td>Kode Perhitungan</td><td>{{ $calc->calculation_code }}</td></tr>
        <tr><td>Nama Perhitungan</td><td>{{ $calc->calculation_name }}</td></tr>
        <tr><td>Kategori Supplier</td><td>{{ $calc->supplier_category }}</td></tr>
        <tr><td>Total Supplier Dievaluasi</td><td>{{ $calc->total_selected }}</td></tr>
        <tr><td>Tanggal Perhitungan</td><td>{{ $calc->calculated_at ? $calc->calculated_at->format('d M Y, H:i') : '-' }}</td></tr>
    
    @elseif($type == 'supplier_dinilai')
        <tr>
            <th>No</th>
            <th>Kode Supplier</th>
            <th>Nama Supplier</th>
            <th>C1</th>
            <th>C2</th>
            <th>C3</th>
            <th>C4</th>
            <th>C5</th>
        </tr>
        @foreach($calc->selectedSuppliers as $idx => $sup)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $sup->supplier_code }}</td>
                <td>{{ $sup->supplier_name }}</td>
                <td>{{ $sup->c1_score }}</td>
                <td>{{ $sup->c2_score }}</td>
                <td>{{ $sup->c3_score }}</td>
                <td>{{ $sup->c4_score }}</td>
                <td>{{ $sup->c5_score }}</td>
            </tr>
        @endforeach

    @elseif($type == 'bobot_reca')
        <tr>
            <th>Rank</th>
            <th>Kode Kriteria</th>
            <th>Nama Kriteria</th>
            <th>Atribut</th>
            <th>Standard Value</th>
            <th>Variation Value</th>
            <th>Deviation Value</th>
            <th>Bobot (W)</th>
            <th>Persentase</th>
        </tr>
        @foreach($calc->recaDetails as $d)
            <tr>
                <td>{{ $d->contribution_rank }}</td>
                <td>{{ $d->criteria_code }}</td>
                <td>{{ $d->criteria_name }}</td>
                <td>{{ $d->attribute }}</td>
                <td>{{ $d->standard_value }}</td>
                <td>{{ $d->variation_value }}</td>
                <td>{{ $d->deviation_value }}</td>
                <td>{{ $d->weight }}</td>
                <td>{{ $d->weight_percentage }}%</td>
            </tr>
        @endforeach

    @elseif($type == 'ranking_maut')
        <tr>
            <th>Rank</th>
            <th>Kode Supplier</th>
            <th>Nama Supplier</th>
            <th>Skor Normalisasi</th>
            <th>Skor Terbobot</th>
            <th>Skor Utilitas Akhir</th>
            <th>Status Rekomendasi</th>
        </tr>
        @foreach($ranks as $r)
            @php $sup = $calc->selectedSuppliers->where('supplier_id', $r->supplier_id)->first(); @endphp
            <tr>
                <td>{{ $r->rank }}</td>
                <td>{{ $sup->supplier_code ?? '-' }}</td>
                <td>{{ $r->supplier_name }}</td>
                <td>{{ is_string($r->normalized_scores) ? $r->normalized_scores : json_encode($r->normalized_scores) }}</td>
                <td>{{ is_string($r->weighted_scores) ? $r->weighted_scores : json_encode($r->weighted_scores) }}</td>
                <td>{{ $r->final_score }}</td>
                <td>{{ $r->rank == 1 ? 'Direkomendasikan' : '-' }}</td>
            </tr>
        @endforeach

    @elseif($type == 'penilaian')
        <tr>
            <th>Kode Supplier</th>
            <th>Nama Supplier</th>
            <th>C1 (Kualitas)</th>
            <th>C2 (Harga)</th>
            <th>C3 (Masa Kerja)</th>
            <th>C4 (Kapasitas)</th>
            <th>C5 (Pengiriman)</th>
            <th>Total Kinerja</th>
            <th>Status Data Lengkap</th>
        </tr>
        @foreach($results as $r)
            <tr>
                <td>{{ $r['supplier_code'] }}</td>
                <td>{{ $r['supplier_name'] }}</td>
                <td>{{ $r['c1'] }}</td>
                <td>{{ $r['c2'] }}</td>
                <td>{{ $r['c3'] }}</td>
                <td>{{ $r['c4'] }}</td>
                <td>{{ $r['c5'] }}</td>
                <td>{{ $r['total'] }}</td>
                <td>{{ $r['status_data'] }}</td>
            </tr>
        @endforeach

    @elseif($type == 'historis')
        <tr>
            <th>No</th>
            <th>No PO</th>
            <th>Tanggal PO</th>
            <th>Tanggal Terima</th>
            <th>Supplier</th>
            <th>Kode Produk</th>
            <th>Nama Produk</th>
            <th>Unit</th>
            <th>Qty Dipesan</th>
            <th>Qty Diterima</th>
            <th>Harga Satuan</th>
            <th>Total Nilai</th>
            <th>Lead Time (Hari)</th>
        </tr>
        @foreach($results as $idx => $r)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $r->no_po }}</td>
                <td>{{ \Carbon\Carbon::parse($r->tanggal_pembelian)->format('Y-m-d') }}</td>
                <td>{{ $r->tanggal_penerimaan ? \Carbon\Carbon::parse($r->tanggal_penerimaan)->format('Y-m-d') : '' }}</td>
                <td>{{ $r->supplier->nama_supplier ?? '-' }}</td>
                <td>{{ $r->kode_produk }}</td>
                <td>{{ $r->nama_produk }}</td>
                <td>{{ $r->unit }}</td>
                <td>{{ $r->qty_pembelian }}</td>
                <td>{{ $r->qty_diterima }}</td>
                <td>{{ $r->harga_satuan }}</td>
                <td>{{ $r->total_nilai }}</td>
                <td>{{ $r->lead_time_hari }}</td>
            </tr>
        @endforeach

    @elseif($type == 'riwayat')
        <tr>
            <th>No</th>
            <th>Kode Perhitungan</th>
            <th>Nama Perhitungan</th>
            <th>Tanggal Proses</th>
            <th>Kategori Supplier</th>
            <th>Kelompok Produk</th>
            <th>Total Dievaluasi</th>
            <th>Status</th>
        </tr>
        @foreach($results as $idx => $r)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $r->calculation_code }}</td>
                <td>{{ $r->calculation_name }}</td>
                <td>{{ $r->calculated_at ? $r->calculated_at->format('Y-m-d H:i') : $r->created_at->format('Y-m-d') }}</td>
                <td>{{ $r->supplier_category }}</td>
                <td>{{ $r->productGroup->nama_kelompok_produk ?? 'Semua' }}</td>
                <td>{{ $r->total_selected }}</td>
                <td>{{ $r->status }}</td>
            </tr>
        @endforeach

    @elseif($type == 'supplier')
        <tr>
            <th>No</th>
            <th>Kode Supplier</th>
            <th>Nama Supplier</th>
            <th>Kategori</th>
            <th>Telepon</th>
            <th>Email</th>
            <th>Alamat</th>
            <th>Masa Kerja Sama (Tahun)</th>
            <th>Status</th>
        </tr>
        @foreach($results as $idx => $r)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $r->kode_supplier }}</td>
                <td>{{ $r->nama_supplier }}</td>
                <td>{{ $r->kategori }}</td>
                <td>{{ $r->nomor_telepon }}</td>
                <td>{{ $r->email }}</td>
                <td>{{ $r->alamat }}</td>
                <td>{{ $r->masa_kerja_sama }}</td>
                <td>{{ $r->status_kerja_sama }}</td>
            </tr>
        @endforeach
    @endif
</table>
