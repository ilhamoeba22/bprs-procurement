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
            margin-top: 10px;
        }

        .content p {
            line-height: 1.4;
            margin: 12px 0 8px 0;
            /* Tambahkan jarak atas 12px */
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
        }

        .details-table th {
            background-color: #f2f2f2;
        }

        .signature-section {
            margin-top: 25px;
            width: 100%;
        }

        .signature-block {
            width: 45%;
            display: inline-block;
            text-align: center;
        }

        .section-title {
            margin: 12px 0 4px 0;
            font-size: 13px;
        }

        .footer {
            margin-top: 18px;
        }

        .footer p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header" style="position: relative;">
            <img src="{{ public_path('images/logo_mci.png') }}" alt="Logo Kiri" style="position: absolute; left: 0; top: 0; height: 50px;">
            <img src="{{ public_path('images/ib_logo.png') }}" alt="Logo Kanan" style="position: absolute; right: 0; top: 0; height: 50px;">
            <h3>SURAT PERINTAH MEMBAYAR</h3>
            <p>Nomor: {{ $kode_pengajuan }}/SPM/OPS/{{ date('m/Y') }}</p>
        </div>

        <div class="content">
            <p>
                Yang bertanda tangan di bawah ini, dengan ini memberikan perintah kepada Divisi Operasional untuk melakukan pembayaran atas pengajuan dengan Nomor: {{ $kode_pengajuan }}/SPM/OPS/{{ date('m/Y') }}, dengan rincian sebagai berikut:
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
                    <th>Tanggal Pengajuan</th>
                    <td>{{ $tanggal_pengajuan }}</td>
                </tr>
                <tr>
                    <th>Pemohon</th>
                    <td>{{ $pemohon }} (Divisi {{ $divisi }})</td>
                </tr>
            </table>

            <h4 class="section-title">2. Rincian Barang & Vendor</h4>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Barang / Vendor</th>
                        <th style="width:8%; text-align:center;">Kuantitas</th>
                        <th style="width:17%;">Harga Satuan</th>
                        <th style="width:20%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <b>{{ $item['barang'] }}</b><br>
                            <span style="font-size:10px; color: #555;">Vendor: {{ $item['vendor'] }}</span>
                        </td>
                        <td style="text-align: center;">{{ $item['kuantitas'] }}</td>
                        <td>Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">TOTAL PERINTAH BAYAR</td>
                        <td>Rp {{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <h4 class="section-title">3. Rincian Pembayaran</h4>
            <table class="details-table payment-details-table">
                @foreach($items as $item)
                <tr>
                    <td colspan="2" style="background-color:#f2f2f2;"><b>Pembayaran untuk: {{ $item['barang'] }}</b></td>
                </tr>
                <tr>
                    <td>Metode / Opsi Pembayaran</td>
                    <td>{{ $item['metode_pembayaran'] }} / {{ $item['opsi_pembayaran'] }}</td>
                </tr>
                @if($item['metode_pembayaran'] == 'Transfer')
                <tr>
                    <td>Rekening Tujuan</td>
                    <td><b>{{ $item['rekening_details']['nama_bank'] }}</b> - {{ $item['rekening_details']['no_rekening'] }} (a.n. {{ $item['rekening_details']['nama_rekening'] }})</td>
                </tr>
                @endif
                @if($item['opsi_pembayaran'] == 'Bisa DP')
                <tr>
                    <td>Pembayaran DP</td>
                    <td><b>Rp {{ number_format($item['dp_details']['nominal_dp'], 0, ',', '.') }}</b> (Tanggal Bayar: {{ $item['dp_details']['tanggal_dp'] }})</td>
                </tr>
                <tr>
                    <td>Pembayaran Lunas</td>
                    <td><b>Rp {{ number_format($item['subtotal'] - $item['dp_details']['nominal_dp'], 0, ',', '.') }}</b> (Tanggal Bayar: {{ $item['tanggal_pelunasan'] }})</td>
                </tr>
                @endif
                @endforeach
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
                        {{-- Blok QR Code Direktur --}}
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
                        {{-- Blok QR Code Kadiv GA --}}
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