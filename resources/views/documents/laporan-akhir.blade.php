<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Akhir Pengadaan - {{ $pengajuan->kode_pengajuan }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
            text-decoration: underline;
        }

        .header p {
            margin: 0;
        }

        .section {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 0;
            overflow: hidden;
        }

        .section-header {
            position: relative;
            background-color: #f2f2f2;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 13px;
            border-bottom: 1px solid #ddd;
        }

        .section-content {
            padding: 10px 12px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 6px 4px;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
        }

        .details-table-4col td {
            width: 25%;
        }

        .details-table-4col .label {
            font-weight: bold;
            width: 18%;
        }

        .details-table-4col .value {
            width: 32%;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .item-table th,
        .item-table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        .item-table th {
            background-color: #f9f9f9;
        }

        .item-table .summary-row td {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .summary-table {
            width: 100%;
            margin-top: 10px;
        }

        .summary-table td {
            padding: 4px 0;
            border: none;
        }

        .summary-table .label {
            text-align: right;
            font-weight: bold;
            width: 80%;
        }

        .summary-table .value {
            text-align: right;
            width: 20%;
        }

        .revision-section {
            border-color: #fbbf24;
        }

        .revision-header {
            background-color: #fef9c3;
            color: #92400e;
            border-bottom-color: #fde68a;
        }

        /* [PERUBAHAN] CSS untuk Stempel LUNAS */
        .paid-stamp {
            font-size: 20px;
            /* Ukuran font dikecilkan */
            font-weight: bold;
            color: #208a39;
            /* Warna teks hijau tua */
            border: 3px solid #a3d9b1;
            /* Border dikecilkan */
            /* Warna border hijau muda */
            background-color: rgba(5, 7, 5, 0.85);
            /* Warna background hijau transparan */
            padding: 15px 8px;
            /* Padding dikecilkan */
            border-radius: 6px;
            /* Border radius dikecilkan */
            text-align: center;
            line-height: 1.2;
            transform: rotate(-15deg);
            margin-top: 15px;
            /* Margin dikecilkan */
        }

        .paid-stamp .date {
            font-size: 10px;
            /* Ukuran font tanggal dikecilkan */
            font-weight: normal;
            display: block;
        }

        /* [PERBAIKAN FINAL] CSS untuk Riwayat Persetujuan */
        .signature-grid {
            text-align: center;
            margin-top: 10px;
            /* Margin atas diperkecil */
        }

        .signature-section-content {
            padding: 5px;
            /* Padding section diperkecil */
        }

        .signature-box {
            display: inline-block;
            vertical-align: top;
            width: 18%;
            padding: 5px;
            text-align: center;
            height: 155px;
            /* Tinggi disesuaikan */
            font-size: 9px;
            margin: 0 0.5%;
        }

        .signature-box p {
            margin: 1px 0;
        }

        .signature-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .signature-name {
            text-decoration: underline;
            font-weight: bold;
            margin-top: 4px;
        }

        .signature-qr {
            height: 70px;
            /* QR Code diperbesar */
            width: 70px;
            /* QR Code diperbesar */
            margin: 4px auto 0 auto;
        }

        .page-break {
            page-break-after: always;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header" style="position: relative;">
            <img src="{{ public_path('images/logo_mci.png') }}" alt="Logo Kiri" style="position: absolute; left: 0; top: 0; height: 45px;">
            <!-- <img src="{{ public_path('images/ib_logo.png') }}" alt="Logo Kanan" style="position: absolute; right: 0; top: 0; height: 30px;"> -->
            <h4>LAPORAN AKHIR PENGADAAN BARANG, JASA, DAN SEWA</h4>
            <p>Nomor: {{ $pengajuan->kode_pengajuan }}</p>
        </div>

        <div class="section">
            <div class="section-header">Detail Pengajuan</div>
            <div class="section-content">
                <table class="details-table details-table-4col">
                    <tr>
                        <td class="label">Kode Pengajuan</td>
                        <td class="value">: {{ $pengajuan->kode_pengajuan }}</td>
                        <td class="label">Tanggal Pengajuan</td>
                        <td class="value">: {{ $pengajuan->created_at->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Pemohon</td>
                        <td class="value">: {{ $pengajuan->pemohon->nama_user }}</td>
                        <td class="label">Divisi</td>
                        <td class="value">: {{ $pengajuan->pemohon->divisi->nama_divisi }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status Final</td>
                        <td class="value">: {{ $pengajuan->status }}</td>
                        <td class="label">Jabatan</td>
                        <td class="value">: {{ $pengajuan->pemohon->jabatan->nama_jabatan }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Rincian Barang yang Diajukan</div>
            <div class="section-content">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Kuantitas</th>
                            <th>Spesifikasi</th>
                            <th>Justifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pengajuan->items as $item)
                        <tr>
                            <td>{{ $item->nama_barang }}</td>
                            <td>{{ $item->kategori_barang }}</td>
                            <td>{{ $item->kuantitas }}</td>
                            <td>{{ $item->spesifikasi }}</td>
                            <td>{{ $item->justifikasi }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($pengajuan->it_recommended_by)
        <div class="section">
            <div class="section-header">Rekomendasi Tim IT</div>
            <div class="section-content">
                <table class="details-table details-table-4col">
                    <tr>
                        <td class="label">Tipe Rekomendasi</td>
                        <td colspan="3">: {{ $pengajuan->rekomendasi_it_tipe }}</td>
                    </tr>
                    <tr>
                        <td class="label">Catatan</td>
                        <td colspan="3">: {{ $pengajuan->rekomendasi_it_catatan }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="section-header">Hasil Survei Harga oleh GA</div>
            <div class="section-content">
                @php
                // Group survei harga by vendor
                $vendorGroups = collect([]);
                foreach($pengajuan->items as $item) {
                foreach($item->surveiHargas as $survei) {
                if(!$vendorGroups->has($survei->nama_vendor)) {
                $vendorGroups[$survei->nama_vendor] = collect([]);
                }
                $vendorGroups[$survei->nama_vendor]->push([
                'item' => $item,
                'survei' => $survei
                ]);
                }
                }
                @endphp

                @foreach($vendorGroups as $vendorName => $items)
                <p class="font-bold" style="margin-top: 10px;">Vendor/Link: {{ $vendorName }}</p>
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th style="width:20%;">Harga</th>
                            <th style="width:25%;">Kondisi Pajak</th>
                            <th style="width:20%;">Nominal Pajak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $data)
                        <tr>
                            <td>{{ $data['item']->nama_barang }}</td>
                            <td>Rp {{ number_format($data['survei']->harga, 0, ',', '.') }}</td>
                            <td>{{ $data['survei']->kondisi_pajak }}</td>
                            <td>Rp {{ number_format($data['survei']->nominal_pajak, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endforeach
            </div>
        </div>

        @if($estimasiBiaya)
        <div class="section">
            <div class="section-header">Rincian Estimasi Biaya (Vendor Terpilih: {{ $finalVendor->nama_vendor }})</div>
            <div class="section-content">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Item/Barang</th>
                            <th style="width:10%; text-align:center;">Kuantitas</th>
                            <th style="width:20%;">Harga Satuan</th>
                            <th style="width:20%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estimasiBiaya['details'] as $detail)
                        <tr>
                            <td>{{ $detail['nama_barang'] }}</td>
                            <td style="text-align: center;">{{ $detail['kuantitas'] }}</td>
                            <td>Rp {{ number_format($detail['harga_satuan'], 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($detail['total_item'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="summary-row">
                            <td colspan="3" style="text-align:right;">SUBTOTAL</td>
                            <td>Rp {{ number_format($estimasiBiaya['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                        <tr class="summary-row">
                            <td colspan="3" style="text-align:right;">Pajak ({{ $estimasiBiaya['pajak_info_text'] }})</td>
                            <td>Rp {{ number_format($estimasiBiaya['pajak_info_nominal'], 0, ',', '.') }}</td>
                        </tr>
                        <tr class="summary-row" style="font-size: 14px;">
                            <td colspan="3" style="text-align:right;">TOTAL BIAYA</td>
                            <td>Rp {{ number_format($estimasiBiaya['total_biaya'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($latestRevisi)
        <div class="section revision-section">
            <div class="section-header revision-header">Detail Proses Revisi Harga</div>
            <div class="section-content">
                <table class="details-table details-table-4col">
                    <tr>
                        <td class="label">Total Harga Awal</td>
                        <td class="value">: Rp {{ number_format($latestRevisi->harga_awal, 0, ',', '.') }}</td>
                        <td class="label">Harga Barang Revisi</td>
                        <td class="value">: Rp {{ number_format($latestRevisi->harga_revisi, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Nominal Pajak Revisi</td>
                        <td class="value">: Rp {{ number_format($latestRevisi->nominal_pajak, 0, ',', '.') }}</td>
                        <td class="label">Total Biaya Revisi</td>
                        <td class="value">: Rp {{ number_format($latestRevisi->harga_revisi + $latestRevisi->nominal_pajak, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Revisi</td>
                        <td class="value">: {{ \Carbon\Carbon::parse($latestRevisi->tanggal_revisi)->translatedFormat('d F Y') }}</td>
                        <td class="label">Alasan Revisi</td>
                        <td class="value">: {{ $latestRevisi->alasan_revisi }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        @if($payment_details)
        <div class="section">
            <div class="section-header">Rincian Pembayaran Final</div>
            <div class="section-content">
                <table style="width: 100%; border: none; border-collapse: collapse;">
                    <tr style="vertical-align: top;">
                        <td style="width: 70%; padding-right: 20px;">
                            <table class="details-table" style="margin:0;">
                                <tr style="background-color:#e0e0e0;">
                                    <td style="width: 30%; font-weight: bold;">TOTAL PERINTAH BAYAR (FINAL)</td>
                                    <td><b>Rp {{ number_format($total_final, 0, ',', '.') }}</b></td>
                                </tr>
                                <tr>
                                    <td style="width: 30%;">Metode Pembayaran</td>
                                    <td><b>{{ $payment_details['metode_pembayaran'] }}</b></td>
                                </tr>
                                @if(strtolower(trim($payment_details['metode_pembayaran'])) == 'transfer')
                                <tr>
                                    <td style="width: 30%;">Rekening Tujuan</td>
                                    <td><b>{{ $payment_details['nama_bank'] }}</b> - {{ $payment_details['no_rekening'] }} (a.n. {{ $payment_details['nama_rekening'] }})</td>
                                </tr>
                                @endif
                                @if($payment_details['opsi_pembayaran'] == 'Bisa DP')
                                <tr>
                                    <td>Pembayaran DP</td>
                                    <td>
                                        <b>Rp {{ number_format($payment_details['nominal_dp'], 0, ',', '.') }}</b>
                                        @if($payment_details['tanggal_dp_aktual'])
                                        <span style="color: #166534;">(Telah Dibayar Tgl. {{ $payment_details['tanggal_dp_aktual'] }})</span>
                                        @else
                                        <span>(Rencana Tgl. {{ $payment_details['tanggal_dp'] }})</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Sisa Pelunasan</td>
                                    <td>
                                        <b>Rp {{ number_format($total_final - $payment_details['nominal_dp'], 0, ',', '.') }}</b>
                                        @if($payment_details['tanggal_pelunasan_aktual'])
                                        <span style="color: #166534;">(Telah Lunas Tgl. {{ $payment_details['tanggal_pelunasan_aktual'] }})</span>
                                        @else
                                        <span>(Rencana Tgl. {{ $payment_details['tanggal_pelunasan'] }})</span>
                                        @endif
                                    </td>
                                </tr>
                                @elseif($payment_details['opsi_pembayaran'] == 'Langsung Lunas')
                                <tr>
                                    <td>Pembayaran Lunas</td>
                                    <td>
                                        <b>Rp {{ number_format($total_final, 0, ',', '.') }}</b>
                                        @if($payment_details['tanggal_pelunasan_aktual'])
                                        <span style="color: #166534;">(Telah Lunas Tgl. {{ $payment_details['tanggal_pelunasan_aktual'] }})</span>
                                        @else
                                        <span>(Rencana Tgl. {{ $payment_details['tanggal_pelunasan'] }})</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                        <td style="width: 30%;">
                            @if($is_paid)
                            <div class="paid-stamp">
                                LUNAS
                                <span class="date">{{ $payment_details['tanggal_pelunasan_aktual'] }}</span>
                            </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="section-header">Budget Control</div>
            <div class="section-content">
                <table class="details-table">
                    <tr>
                        <td class="label">Status Budget</td>
                        <td>: {{ $pengajuan->status_budget ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Catatan Budget</td>
                        <td>: {{ $pengajuan->catatan_budget ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Catatan Approval</div>
            <div class="section-content">
                <table class="details-table">
                    @if(!empty(trim($pengajuan->catatan_revisi)))
                    <tr>
                        <td class="label">Catatan Atasan (Manager/Kadiv)</td>
                        <td>: {!! nl2br(e(trim($pengajuan->catatan_revisi))) !!}</td>
                    </tr>
                    @endif
                    @if(!empty($pengajuan->kadiv_ga_catatan))
                    <tr>
                        <td class="label">Catatan Kadiv GA</td>
                        <td>: {{ $pengajuan->kadiv_ga_catatan }}</td>
                    </tr>
                    @endif
                    @if(!empty($pengajuan->direktur_operasional_catatan))
                    <tr>
                        <td class="label">Catatan Direktur Operasional</td>
                        <td>: {{ $pengajuan->direktur_operasional_catatan }}</td>
                    </tr>
                    @endif
                    @if(!empty($pengajuan->direktur_utama_catatan))
                    <tr>
                        <td class="label">Catatan Direktur Utama</td>
                        <td>: {{ $pengajuan->direktur_utama_catatan }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- <div class="page-break"></div> -->

        <div class="section">
            <div class="section-header">Riwayat Persetujuan</div>
            <div class="section-content">
                <div class="signature-grid">
                    <div class="signature-box">
                        <p class="signature-title">Diajukan oleh</p>
                        <img src="{{ $qrCodes['pemohon'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->pemohon->nama_user }}</p>
                        <p>{{ $pengajuan->pemohon->jabatan->nama_jabatan }}</p>
                        <p>{{ $pengajuan->created_at->translatedFormat('d F Y') }}</p>
                    </div>

                    @if($atasan)
                    <div class="signature-box">
                        <p class="signature-title">Disetujui Atasan</p>
                        <img src="{{ $qrCodes['atasan'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $atasan->nama_user }}</p>
                        <p>{{ $atasan->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->kadiv_approved_at ?? $pengajuan->manager_approved_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($pengajuan->recommenderIt)
                    <div class="signature-box">
                        <p class="signature-title">Direkomendasikan IT</p>
                        <img src="{{ $qrCodes['it'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->recommenderIt->nama_user }}</p>
                        <p>{{ $pengajuan->recommenderIt->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->it_recommended_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($pengajuan->surveyorGa)
                    <div class="signature-box">
                        <p class="signature-title">Survei oleh</p>
                        <img src="{{ $qrCodes['ga_surveyor'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->surveyorGa->nama_user }}</p>
                        <p>{{ $pengajuan->surveyorGa->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->ga_surveyed_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($pengajuan->approverBudget)
                    <div class="signature-box">
                        <p class="signature-title">Budget Control</p>
                        <img src="{{ $qrCodes['budget_approver'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->approverBudget->nama_user }}</p>
                        <p>{{ $pengajuan->approverBudget->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->budget_approved_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($pengajuan->validatorBudgetOps)
                    <div class="signature-box">
                        <p class="signature-title">Validasi Budget</p>
                        <img src="{{ $qrCodes['budget_validator'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->validatorBudgetOps->nama_user }}</p>
                        <p>{{ $pengajuan->validatorBudgetOps->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->kadiv_ops_budget_approved_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($pengajuan->approverKadivGa)
                    <div class="signature-box">
                        <p class="signature-title">Disetujui Kadiv GA</p>
                        <img src="{{ $qrCodes['kadiv_ga'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->approverKadivGa->nama_user }}</p>
                        <p>{{ $pengajuan->approverKadivGa->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->kadiv_ga_approved_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($direksi)
                    <div class="signature-box">
                        <p class="signature-title">Disetujui Direksi</p>
                        <img src="{{ $qrCodes['direksi'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $direksi->nama_user }}</p>
                        <p>{{ $direksi->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->direktur_utama_approved_at ?? $pengajuan->direktur_operasional_approved_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif

                    @if($pengajuan->disbursedBy)
                    <div class="signature-box">
                        <p class="signature-title">Dibayarkan oleh</p>
                        <img src="{{ $qrCodes['pembayar'] }}" alt="QR Code" class="signature-qr">
                        <p class="signature-name">{{ $pengajuan->disbursedBy->nama_user }}</p>
                        <p>{{ $pengajuan->disbursedBy->jabatan->nama_jabatan }}</p>
                        <p>{{ \Carbon\Carbon::parse($pengajuan->disbursed_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>

</html>