<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Pengadaan Barang, Jasa, dan Sewa</title>
    <style>
        @page {
            margin: 25px 35px;
        }

        body {
            font-family: 'Roboto', 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            padding: 10px 0;
            border-bottom: 2px solid #000000;
            height: 80px;
        }

        .header img {
            height: 60px;
            position: absolute;
            top: 10px;
        }

        .header img:first-child {
            left: 20px;
        }

        .header img:last-child {
            right: 20px;
        }

        .header h3 {
            font-size: 18px;
            margin: 0;
            padding: 0;
            font-weight: 700;
            color: #1a1a1a;
            text-transform: uppercase;
            line-height: 70px;
        }

        .section-title {
            font-size: 12px;
            margin: 10px 0 10px;
            padding: 5px 10px;
            background-color: #f5f5f5;
            border-left: 4px solid #007bff;
            font-weight: 600;
            text-transform: uppercase;
        }

        .details-table,
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #dddddd;
        }

        .details-table th,
        .details-table td,
        .info-table th,
        .info-table td {
            border: 1px solid #dddddd;
            padding: 5px 8px;
            text-align: left;
            vertical-align: top;
        }

        .details-table th,
        .info-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #1a1a1a;
        }

        .sub-table th,
        .sub-table td {
            padding: 4px 8px;
        }

        .signature-box {
            text-align: center;
            width: 100%;
            margin-top: 0;
            padding-top: 10px;
        }

        .signature-box.ga-signature {
            border-top: none;
        }

        .signature-box img {
            height: 80px;
            margin: 5px 0;
        }

        .signature-box p {
            margin: 0;
            padding: 2px 0;
            font-size: 9px;
        }

        .signature-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 5px;
            font-size: 10px;
        }

        .note {
            font-size: 8px;
            color: #666666;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="header" style="position: relative;">
        <img src="{{ public_path('images/logo_mci.png') }}" alt="Logo Kiri" style="position: absolute; left: 0; top: 0; height: 50px;">
        <img src="{{ public_path('images/ib_logo.png') }}" alt="Logo Kanan" style="position: absolute; right: 0; top: 0; height: 50px;">
        <h3>Laporan Pengadaan Barang, Jasa, dan Sewa</h3>
    </div>

    <h4 class="section-title">Informasi Pemohon</h4>
    <table class="info-table">
        <thead>
            <tr>
                <th>Kode Pengajuan</th>
                <th>Nama</th>
                <th>Kantor</th>
            </tr>
            <tr>
                <td>{{ $pengajuan->kode_pengajuan }}</td>
                <td>{{ $pengajuan->pemohon->nama_user ?? 'N/A' }}</td>
                <td>{{ $pengajuan->pemohon->kantor->nama_kantor ?? 'N/A' }}</td>
            </tr>

        </thead>
        <tbody>
            <tr>
                <th>NIK</th>
                <th>Divisi</th>
                <th>Jabatan</th>
            </tr>
            <tr>
                <td>{{ $pengajuan->pemohon->nik ?? 'N/A' }}</td>
                <td>{{ $pengajuan->pemohon->divisi->nama_divisi ?? 'N/A' }}</td>
                <td>{{ (json_decode($pengajuan->pemohon->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</td>
            </tr>
        </tbody>
    </table>

    <h4 class="section-title">Detail Permohonan</h4>
    <table class="details-table">
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Nama Barang</th>
                <th>Spesifikasi</th>
                <th style="width:5%; text-align:center;">Qty</th>
                <th style="width:15%; text-align:right;">Harga Final</th>
                <th style="width:15%; text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pengajuan->items as $item)
            <tr>
                <td>{{ $item->kategori_barang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td>{{ $item->spesifikasi }}</td>
                <td style="text-align:center;">{{ $item->kuantitas }}</td>
                <td style="text-align:right;">Rp {{ number_format($item->vendorFinal->harga ?? 0, 0, ',', '.') }}</td>
                <td style="text-align:right;">Rp {{ number_format(($item->vendorFinal->harga ?? 0) * $item->kuantitas, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr style="font-weight:600;">
                <td colspan="5" style="text-align:right;">TOTAL</td>
                <td style="text-align:right;">Rp {{ number_format($pengajuan->total_nilai, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width:100%; margin-top:10px;">
        <tr>
            <td style="width:50%; vertical-align:top; text-align:center;">
                <div class="signature-box">
                    <p>Pemohon</p>
                    <img src="{{ $pemohonQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $pengajuan->pemohon->nama_user }}</p>
                    <p>{{ (json_decode($pengajuan->pemohon->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</p>
                </div>
            </td>
            @if($atasan)
            <td style="width:50%; vertical-align:top; text-align:center;">
                <div class="signature-box">
                    <p>Disetujui Atasan</p>
                    <img src="{{ $atasanQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $atasan->nama_user }}</p>
                    <p>{{ (json_decode($atasan->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</p>
                </div>
            </td>
            @endif
        </tr>
    </table>

    @if($pengajuan->recommenderIt)
    <h4 class="section-title">Rekomendasi IT</h4>
    <table class="info-table">
        <thead>
            <tr>
                <th>Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $pengajuan->rekomendasi_it_tipe }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <h4 class="section-title">Review General Affairs (GA)</h4>
    <table style="width:100%;">
        <tr>
            <td style="width:65%; vertical-align:top; padding-right:10px;">
                <table class="details-table sub-table">
                    <thead>
                        <tr>
                            <th style="width:5%;">No</th>
                            <th style="width:25%;">Skenario</th>
                            <th style="width:35%;">Nama Vendor</th>
                            <th style="width:35%; text-align:right;">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($pengajuan->items as $item)
                        @foreach($item->surveiHargas->groupBy('tipe_survei') as $tipe => $surveiGroup)
                        @foreach($surveiGroup as $survei)
                        <tr>
                            <td style="text-align:center;">{{ $no++ }}</td>
                            <td>
                                Skenario
                                @if(is_numeric($tipe))
                                {{ $tipe }}
                                @else
                                @if(strtolower($tipe) == '1' || strtolower($tipe) == 'skenario 1') 1
                                @elseif(strtolower($tipe) == '2' || strtolower($tipe) == 'skenario 2') 2
                                @elseif(strtolower($tipe) == '3' || strtolower($tipe) == 'skenario 3') 3
                                @else
                                {{ $tipe }}
                                @endif
                                @endif
                            </td>
                            <td>{{ $survei->nama_vendor }}</td>
                            <td style="text-align:right;">Rp {{ number_format($survei->harga, 0, ',', '.') }} / Item</td>
                        </tr>
                        @endforeach
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td style="width:35%; vertical-align:top; text-align:center; padding-top:0;">
                <div class="signature-box ga-signature">
                    <p>Petugas Review GA</p>
                    <img src="{{ $surveyorGaQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $pengajuan->surveyorGa->nama_user }}</p>
                    <p>{{ (json_decode($pengajuan->surveyorGa->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</p>
                </div>
            </td>
        </tr>
    </table>

    <h4 class="section-title">Review Budget Control</h4>
    <table class="info-table">
        <thead>
            <tr>
                <th>Status Budget</th>
                <th>Catatan Budget</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $pengajuan->kadiv_ga_decision_type === 'Pengadaan' ? $pengajuan->budget_status_pengadaan : $pengajuan->budget_status_perbaikan }}</td>
                <td>{{ $pengajuan->kadiv_ga_decision_type === 'Pengadaan' ? $pengajuan->budget_catatan_pengadaan : $pengajuan->budget_catatan_perbaikan }}</td>
            </tr>
        </tbody>
    </table>
    <table style="width:100%; margin-top:10px;">
        <tr>
            <td style="width:50%; vertical-align:top; text-align:center;">
                <div class="signature-box">
                    <p>Petugas Budget</p>
                    <img src="{{ $approverBudgetQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $pengajuan->approverBudget->nama_user }}</p>
                    <p>{{ (json_decode($pengajuan->approverBudget->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</p>
                </div>
            </td>
            <td style="width:50%; vertical-align:top; text-align:center;">
                <div class="signature-box">
                    <p>Validasi Budget</p>
                    <img src="{{ $validatorBudgetOpsQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $pengajuan->validatorBudgetOps->nama_user }}</p>
                    <p>{{ (json_decode($pengajuan->validatorBudgetOps->jabatan, true)['nama_jabatan'] ?? 'N/A') }}</p>
                </div>
            </td>
        </tr>
    </table>

    <h4 class="section-title">Detail Pembayaran</h4>
    <table class="details-table">
        <thead>
            <tr>
                <th>Pembayaran</th>
                <th>Metode / Opsi</th>
                <th>Rekening Tujuan</th>
                <th>Pembayaran DP</th>
                <th>Pembayaran Lunas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pengajuan->items as $item)
            @if($item->vendorFinal)
            <tr>
                <td style="background-color:#eef; font-weight:600;">{{ $item->nama_barang }} ke {{ $item->vendorFinal->nama_vendor }}</td>
                <td>{{ $item->vendorFinal->metode_pembayaran }} / {{ $item->vendorFinal->opsi_pembayaran }}</td>
                <td>@if($item->vendorFinal->metode_pembayaran == 'Transfer')<b>{{ $item->vendorFinal->nama_bank }}</b> - {{ $item->vendorFinal->no_rekening }} (a.n. {{ $item->vendorFinal->nama_rekening }})@else-@endif</td>
                <td>@if($item->vendorFinal->opsi_pembayaran == 'Bisa DP')<b>Rp {{ number_format($item->vendorFinal->nominal_dp, 0, ',', '.') }}</b> (Dibayar Tgl: {{ $item->vendorFinal->tanggal_dp ? \Carbon\Carbon::parse($item->vendorFinal->tanggal_dp)->translatedFormat('d M Y') : '-' }})@else-@endif</td>
                <td>
                    <b>Rp {{ number_format(
                        ($item->vendorFinal->harga ?? 0) * $item->kuantitas - ($item->vendorFinal->opsi_pembayaran == 'Bisa DP' ? ($item->vendorFinal->nominal_dp ?? 0) : 0),
                        0, ',', '.'
                    ) }}</b>
                    (Dibayar Tgl: {{ $item->vendorFinal->tanggal_pelunasan ? \Carbon\Carbon::parse($item->vendorFinal->tanggal_pelunasan)->translatedFormat('d M Y') : '-' }})
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <table style="width:100%; margin-top:10px;">
        <tr>
            <td style="width:50%; vertical-align:top; text-align:center;">
                <div class="signature-box">
                    <p>Disetujui oleh</p>
                    <img src="{{ $approverKadivGaQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $pengajuan->approverKadivGa->nama_user }}</p>
                    <p>Kepala Divisi GA</p>
                    <p class="note">Catatan: {{ $pengajuan->kadiv_ga_catatan ?? '-'}}</p>
                </div>
            </td>
            @if($direksi)
            <td style="width:50%; vertical-align:top; text-align:center;">
                <div class="signature-box">
                    <p>Mengetahui</p>
                    <img src="{{ $direksiQrCode }}" alt="QR Code">
                    <p class="signature-name">{{ $direksi->nama_user }}</p>
                    <p>{{ $direksi->jabatan ? (json_decode($direksi->jabatan, true)['nama_jabatan'] ?? 'N/A') : 'Direksi' }}</p>
                </div>
            </td>
            @endif
        </tr>
    </table>

</body>

</html>