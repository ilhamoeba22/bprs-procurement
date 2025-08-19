<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Akhir Pengadaan - {{ $pengajuan->kode_pengajuan }}</title>
    <style>
        @page {
            margin: 25px 35px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h3 {
            font-size: 16px;
            margin: 0;
            font-weight: bold;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1.5px solid #3498db;
        }

        .info-table,
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table th,
        .info-table td,
        .item-table th,
        .item-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        .info-table th {
            background-color: #f2f2f2;
            width: 180px;
        }

        .item-table th {
            background-color: #ecf0f1;
            text-align: center;
        }

        /* === SIGNATURE STYLING (New & Improved) === */
        .signature-container {
            margin-top: 15px;
            page-break-inside: avoid;
            clear: both;
        }

        .signature-container::after {
            content: "";
            clear: both;
            display: table;
        }

        .signature-box {
            width: 49%;
            float: left;
        }

        .signature-box-right {
            float: right;
        }

        .signature-content {
            display: block;
            clear: both;
        }

        .signature-qr {
            float: left;
            width: 60px;
            height: 60px;
        }

        .signature-qr img {
            width: 60px;
            height: 60px;
        }

        .signature-details {
            float: left;
            margin-left: 10px;
            padding-top: 2px;
        }

        .signature-details p {
            margin: 0;
            line-height: 1.4;
        }

        .signature-details .title {
            font-weight: bold;
            font-size: 10px;
            color: #7f8c8d;
        }

        .signature-details .name {
            font-weight: bold;
            font-size: 11px;
        }

        .signature-details .date {
            font-size: 9px;
            color: #95a5a6;
        }

        /* ========================================= */

        .revisi-section {
            background-color: #fff9e6;
            border: 1.5px solid #ffc107;
            padding: 10px;
            margin: 20px 0;
        }

        .revisi-title {
            color: #d35400;
            border-bottom-color: #f39c12;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    @php $sectionCounter = 1; @endphp

    <div class="header">
        <h3>LAPORAN AKHIR PENGADAAN</h3>
    </div>

    {{-- ==================== 1. DETAIL PENGAJUAN & PERSETUJUAN AWAL ==================== --}}
    <div class="section-title">{{ $sectionCounter++ }}. Detail Pengajuan</div>
    <table class="info-table">
        <tr>
            <th>Kode Pengajuan</th>
            <td><b>{{ $pengajuan->kode_pengajuan }}</b></td>
        </tr>
        <tr>
            <th>Tanggal Pengajuan</th>
            <td>{{ $pengajuan->created_at->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Pemohon</th>
            <td>{{ $pengajuan->pemohon->nama_user ?? 'N/A' }} ({{ $pengajuan->pemohon->divisi->nama_divisi ?? 'N/A' }})</td>
        </tr>
    </table>
    <table class="item-table">
        <thead>
            <tr>
                <th>Nama Barang & Kategori</th>
                <th style="width:5%;">Qty</th>
                <th>Justifikasi Kebutuhan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pengajuan->items as $item)
            <tr>
                <td><b>{{ $item->nama_barang }}</b><br><small>Kategori: {{ $item->kategori_barang }}</small></td>
                <td style="text-align:center;">{{ $item->kuantitas }}</td>
                <td>{{ $item->justifikasi }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-container">
        <div class="signature-box">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['pemohon']))<img src="{{ $qrCodes['pemohon'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Diajukan oleh:</p>
                    <p class="name">{{ $pengajuan->pemohon->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ $pengajuan->created_at->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
        @if($atasan)
        <div class="signature-box signature-box-right">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['atasan']))<img src="{{ $qrCodes['atasan'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Disetujui oleh Atasan:</p>
                    <p class="name">{{ $atasan->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ \Carbon\Carbon::parse($pengajuan->manager_approved_at ?? $pengajuan->kadiv_approved_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ==================== 2. REKOMENDASI IT (JIKA ADA) ==================== --}}
    @if($pengajuan->recommenderIt)
    <div class="section-title">{{ $sectionCounter++ }}. Rekomendasi Teknis IT</div>
    <table class="info-table">
        <tr>
            <th>Tipe Rekomendasi</th>
            <td>{{ $pengajuan->rekomendasi_it_tipe }}</td>
        </tr>
        <tr>
            <th>Catatan</th>
            <td>{{ $pengajuan->rekomendasi_it_catatan }}</td>
        </tr>
    </table>
    <div class="signature-container">
        <div class="signature-box">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['it']))<img src="{{ $qrCodes['it'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Direkomendasikan oleh:</p>
                    <p class="name">{{ $pengajuan->recommenderIt->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ \Carbon\Carbon::parse($pengajuan->it_recommended_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== 3. SURVEI GENERAL AFFAIRS ==================== --}}
    <div class="section-title">{{ $sectionCounter++ }}. Hasil Survei General Affairs</div>
    <p>Vendor terpilih: <b>{{ $finalVendor->nama_vendor ?? 'N/A' }}</b></p>
    <div class="signature-container">
        <div class="signature-box">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['ga_surveyor']))<img src="{{ $qrCodes['ga_surveyor'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Disurvei oleh:</p>
                    <p class="name">{{ $pengajuan->surveyorGa->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ \Carbon\Carbon::parse($pengajuan->ga_surveyed_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>


    {{-- ==================== KONDISIONAL: ALUR PERSETUJUAN ==================== --}}
    @if($latestRevisi)
    {{-- Tampilan jika ada REVISI --}}
    <div class="revisi-section">
        <div class="section-title revisi-title">{{ $sectionCounter++ }}. Proses Revisi & Persetujuan Final</div>
        <table class="info-table">
            <tr>
                <th>Alasan Revisi</th>
                <td>{{ $latestRevisi->alasan_revisi }} (oleh {{ $latestRevisi->direvisiOleh->nama_user ?? 'N/A' }})</td>
            </tr>
            <tr>
                <th>Harga Awal</th>
                <td>Rp {{ number_format($latestRevisi->harga_awal, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <th>Harga Final (Setelah Revisi + Pajak)</th>
                <td>Rp {{ number_format($latestRevisi->harga_revisi + $latestRevisi->nominal_pajak, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="signature-container">
            {{-- Budget (Revisi) --}}
            <div class="signature-box">
                <div class="signature-content">
                    <div class="signature-qr">@if(isset($qrCodes['budget_approver']))<img src="{{ $qrCodes['budget_approver'] }}">@endif</div>
                    <div class="signature-details">
                        <p class="title">Budget Review (Revisi):</p>
                        <p class="name">{{ $latestRevisi->revisiBudgetApprover->nama_user ?? 'N/A' }}</p>
                        <p class="date">Status: {{ $latestRevisi->revisi_budget_status_pengadaan }}</p>
                    </div>
                </div>
            </div>
            {{-- Validator (Revisi) --}}
            <div class="signature-box signature-box-right">
                <div class="signature-content">
                    <div class="signature-qr">@if(isset($qrCodes['budget_validator']))<img src="{{ $qrCodes['budget_validator'] }}">@endif</div>
                    <div class="signature-details">
                        <p class="title">Validasi Budget (Revisi):</p>
                        <p class="name">{{ $latestRevisi->revisiBudgetValidator->nama_user ?? 'N/A' }}</p>
                        <p class="date">Pada: {{ \Carbon\Carbon::parse($latestRevisi->revisi_budget_validated_at)->translatedFormat('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="signature-container" style="margin-top:20px;">
            {{-- Kadiv GA (Revisi) --}}
            <div class="signature-box">
                <div class="signature-content">
                    <div class="signature-qr">@if(isset($qrCodes['kadiv_ga']))<img src="{{ $qrCodes['kadiv_ga'] }}">@endif</div>
                    <div class="signature-details">
                        <p class="title">Menyetujui (Revisi):</p>
                        <p class="name">{{ $latestRevisi->revisiKadivGaApprover->nama_user ?? 'N/A' }}</p>
                        <p class="date">Keputusan: {{ $latestRevisi->revisi_kadiv_ga_decision_type }}</p>
                    </div>
                </div>
            </div>
            {{-- Direksi (Revisi) --}}
            @if($direksi)
            <div class="signature-box signature-box-right">
                <div class="signature-content">
                    <div class="signature-qr">@if(isset($qrCodes['direksi']))<img src="{{ $qrCodes['direksi'] }}">@endif</div>
                    <div class="signature-details">
                        <p class="title">Mengetahui (Revisi):</p>
                        <p class="name">{{ $direksi->nama_user ?? 'N/A' }}</p>
                        <p class="date">Keputusan: {{ $latestRevisi->revisi_direktur_operasional_decision_type ?? $latestRevisi->revisi_direktur_utama_decision_type }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    {{-- Tampilan jika TANPA REVISI --}}
    <div class="section-title">{{ $sectionCounter++ }}. Validasi Budget & Persetujuan Final</div>
    <table class="info-table">
        <tr>
            <th>Status Budget</th>
            <td>{{ $pengajuan->status_budget }}</td>
        </tr>
        <tr class="total-row">
            <th>Total Biaya Final</th>
            <td>Rp {{ number_format($pengajuan->total_nilai, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="signature-container">
        {{-- Budget --}}
        <div class="signature-box">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['budget_approver']))<img src="{{ $qrCodes['budget_approver'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Budget Direview oleh:</p>
                    <p class="name">{{ $pengajuan->approverBudget->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ \Carbon\Carbon::parse($pengajuan->budget_approved_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
        {{-- Validator --}}
        <div class="signature-box signature-box-right">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['budget_validator']))<img src="{{ $qrCodes['budget_validator'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Budget Divalidasi oleh:</p>
                    <p class="name">{{ $pengajuan->validatorBudgetOps->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ \Carbon\Carbon::parse($pengajuan->kadiv_ops_budget_approved_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="signature-container" style="margin-top:20px;">
        {{-- Kadiv GA --}}
        <div class="signature-box">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['kadiv_ga']))<img src="{{ $qrCodes['kadiv_ga'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Menyetujui:</p>
                    <p class="name">{{ $pengajuan->approverKadivGa->nama_user ?? 'N/A' }}</p>
                    <p class="date">Keputusan: {{ $pengajuan->kadiv_ga_decision_type }}</p>
                </div>
            </div>
        </div>
        {{-- Direksi --}}
        @if($direksi)
        <div class="signature-box signature-box-right">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['direksi']))<img src="{{ $qrCodes['direksi'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Mengetahui:</p>
                    <p class="name">{{ $direksi->nama_user ?? 'N/A' }}</p>
                    <p class="date">Keputusan: {{ $pengajuan->direktur_operasional_decision_type ?? $pengajuan->direktur_utama_decision_type }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ==================== 4. PEMBAYARAN FINAL ==================== --}}
    <div class="section-title">{{ $sectionCounter++ }}. Detail Pembayaran Final</div>
    @php
    $finalTotal = $latestRevisi ? ($latestRevisi->harga_revisi + $latestRevisi->nominal_pajak) : $pengajuan->total_nilai;
    @endphp
    <table class="info-table">
        <tr class="total-row">
            <th>Total Final Dibayarkan</th>
            <td>Rp {{ number_format($finalTotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Vendor Penerima</th>
            <td>{{ $finalVendor->nama_vendor ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Rekening Tujuan</th>
            <td>{{ $finalVendor->nama_bank ?? '' }} - {{ $finalVendor->no_rekening ?? '' }} (a.n {{ $finalVendor->nama_rekening ?? '' }})</td>
        </tr>
    </table>

    @if($pengajuan->disbursedBy)
    <div class="signature-container">
        <div class="signature-box">
            <div class="signature-content">
                <div class="signature-qr">@if(isset($qrCodes['pembayar']))<img src="{{ $qrCodes['pembayar'] }}">@endif</div>
                <div class="signature-details">
                    <p class="title">Dibayarkan oleh Finance:</p>
                    <p class="name">{{ $pengajuan->disbursedBy->nama_user ?? 'N/A' }}</p>
                    <p class="date">Pada: {{ \Carbon\Carbon::parse($pengajuan->disbursed_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</body>

</html>