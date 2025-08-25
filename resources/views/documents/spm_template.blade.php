<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Surat Perintah Membayar</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h3 {
            margin: 0 0 2px 0;
            font-size: 18px;
            text-decoration: underline;
        }

        .header p {
            margin: 2px 0 0 0;
        }

        .content {
            margin-top: 15px;
        }

        .content p {
            line-height: 1.4;
            margin: 12px 0 0 0;
            text-align: justify;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .details-table th,
        .details-table td {
            border: 1px solid #999;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }

        .details-table th {
            background-color: #f2f2f2;
        }

        .signature-section {
            margin-top: 20px;
            width: 100%;
        }

        .section-title {
            margin: 15px 0 4px 0;
            font-size: 13px;
        }

        .footer {
            margin-top: 18px;
        }

        .footer p {
            margin: 0;
        }

        .section-header-container {
            position: relative;
            margin-bottom: 4px;
        }

        .paid-stamp-small {
            position: absolute;
            right: 20px;
            top: 70px;
            transform: rotate(-10deg);
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            border: 3px solid #28a745;
            padding: 5px 10px;
            border-radius: 5px;
            text-align: center;
            background-color: #fff;
            opacity: 0.9;
            line-height: 1.2;
        }

        .paid-stamp-small .date {
            font-size: 10px;
            font-weight: normal;
            display: block;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header" style="position: relative;">
            <img src="{{ public_path('images/logo_mci.png') }}" alt="Logo Kiri" style="position: absolute; left: 0; top: 0; height: 45px;">
            <!-- <img src="{{ public_path('images/ib_logo.png') }}" alt="Logo Kanan" style="position: absolute; right: 0; top: 0; height: 50px;"> -->
            <h3>SURAT PERINTAH MEMBAYAR @if($is_revisi)(REVISI)@endif</h3>
            <p>Nomor: {{ $kode_pengajuan }}/SPM/OPS/{{ date('m/Y') }}</p>
        </div>

        <div class="content">
            <p>
                Yang bertanda tangan di bawah ini, dengan ini memberikan perintah kepada Divisi Operasional untuk melakukan pembayaran atas pengajuan dengan Nomor: {{ $kode_pengajuan }}, dengan rincian sebagai berikut:
            </p>
        </div>

        <div class="signature-section">
            <h4 class="section-title">1. Detail Pengajuan</h4>
            <table class="details-table">
                <tr>
                    <th style="width: 150px;">Kode Pengajuan</th>
                    <td>{{ $kode_pengajuan }}</td>
                </tr>
                <tr>
                    <th>Pemohon</th>
                    <td>{{ $pemohon }} (Divisi {{ $divisi }})</td>
                </tr>
            </table>

            <h4 class="section-title">@if($is_revisi) 2. Detail Harga Awal (Sebelum Revisi) @else 2. Detail Harga @endif</h4>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th style="width:8%; text-align:center;">Qty</th>
                        <th style="width:17%;">Harga Satuan</th>
                        <th style="width:20%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items_original as $item)
                    <tr>
                        <td><b>{{ $item['barang'] }}</b></td>
                        <td style="text-align: center;">{{ $item['kuantitas'] }}</td>
                        <td>Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">TOTAL NILAI BARANG (AWAL)</td>
                        <td><b>Rp {{ number_format($total_nilai_barang_original, 0, ',', '.') }}</b></td>
                    </tr>
                    @if($total_pajak_original > 0)
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">PAJAK (AWAL)</td>
                        <td><b>Rp {{ number_format($total_pajak_original, 0, ',', '.') }}</b></td>
                    </tr>
                    @endif
                    <tr class="total-row" style="background-color:#e0e0e0;">
                        <td colspan="3" style="text-align:right;"><b>TOTAL BIAYA (AWAL)</b></td>
                        <td><b>Rp {{ number_format($total_biaya_original, 0, ',', '.') }}</b></td>
                    </tr>
                </tbody>
            </table>

            @if($is_revisi && $revision_details)
            <h4 class="section-title" style="color: #c2410c;">3. Detail Revisi Harga</h4>
            <table class="details-table" style="background-color: #fffbeb;">
                <tr>
                    <td colspan="2" style="text-align:center; background-color:#fde047;"><b>Ringkasan Perubahan (Tanggal: {{ $revision_details['tanggal_revisi'] }})</b></td>
                </tr>
                <tr>
                    <td style="width: 30%;">Total Biaya Lama (Barang + Pajak)</td>
                    <td><b>Rp {{ number_format($total_biaya_original, 0, ',', '.') }}</b></td>
                </tr>
                <tr>
                    <td>Selisih Total Biaya</td>
                    <td>
                        <b style="color: {{ $revision_details['selisih_total'] >= 0 ? '#166534' : '#991b1b' }};">
                            Rp {{ number_format(abs($revision_details['selisih_total']), 0, ',', '.') }}
                        </b>
                        ({{ $revision_details['selisih_total'] >= 0 ? 'Kenaikan' : 'Pengurangan' }})
                    </td>
                </tr>
                <tr>
                    <td>Alasan Revisi</td>
                    <td>{{ $revision_details['alasan_revisi'] }}</td>
                </tr>
                <tr>
                    <td style="width: 30%;">Total Biaya Revisi (Barang + Pajak)</td>
                    <td><b>Rp {{ number_format($total_final, 0, ',', '.') }}</b></td>
                </tr>
            </table>
            @endif

            <div class="section-header-container">
                <h4 class="section-title">@if($is_revisi) 4. @else 3. @endif Rincian Pembayaran Final kepada: {{ $payment_details['vendor'] }}</h4>
                @if($is_paid)
                <div class="paid-stamp-small">
                    LUNAS
                    <span class="date">{{ $payment_details['tanggal_pelunasan_aktual'] }}</span>
                </div>
                @endif
            </div>
            <table class="details-table payment-details-table">
                <tr style="background-color:#e0e0e0;">
                    <td style="width: 30%;"><b>TOTAL PERINTAH BAYAR (FINAL)</b></td>
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

                {{-- Logika baru untuk menampilkan detail pembayaran DP atau Lunas --}}
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
        </div>

        <div class="footer">
            <p>
                Demikian Surat Perintah Membayar ini dibuat untuk dapat dilaksanakan sebagaimana mestinya. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.
            </p>
        </div>
        <div class="signature-section">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="width:50%; border:none;"></td>
                    <td style="width:50%; border:none; text-align:center; vertical-align:top; padding-top:10px;">
                        Depok, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="width:50%; text-align:center;">
                        @if($direkturName)
                        <p style="margin-bottom:2px;">Mengetahui,</p>
                        <p style="margin-bottom:2px;">{{ $direkturJabatan }}</p>
                        <br>
                        @if(!empty($direkturQrCode))
                        <img src="{{ $direkturQrCode }}" alt="QR Code Verifikasi">
                        @else
                        <br><br><br>
                        @endif
                        <p style="margin-top:5px;"><b><u>{{ $direkturName }}</u></b></p>
                        @endif
                    </td>
                    <td style="width:50%; text-align:center;">
                        <p style="margin-bottom:2px;">Menyetujui,</p>
                        <p style="margin-bottom:2px;">Kepala Divisi GA</p>
                        <br>
                        @if(!empty($kadivGaQrCode))
                        <img src="{{ $kadivGaQrCode }}" alt="QR Code Verifikasi">
                        @else
                        <br><br><br>
                        @endif
                        <p style="margin-top:5px;"><b><u>{{ $kadivGaName }}</u></b></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>