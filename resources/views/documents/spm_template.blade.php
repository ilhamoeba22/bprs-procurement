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
            right: 40px;
            top: 70px;
            transform: rotate(-10deg);
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            border: 3px solid rgba(40, 167, 69, 0.5);
            padding: 5px 10px;
            border-radius: 5px;
            text-align: center;
            background-color: transparent;
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
                        <td colspan="3" style="text-align:right;">TOTAL NILAI BARANG</td>
                        <td><b>Rp {{ number_format($total_nilai_barang_original, 0, ',', '.') }}</b></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">
                            @php
                            // Membuat label pajak yang dinamis
                            $pajakLabel = 'PAJAK';
                            if ($tax_type_original) {
                            $pajakLabel = $tax_type_original;
                            }
                            if ($tax_condition_original !== 'Tidak Ada Pajak') {
                            $pajakLabel .= str_contains($tax_condition_original, 'Include') ? ' (Include)' : ' (Exclude)';
                            }
                            @endphp
                            {{ $pajakLabel }}
                        </td>
                        <td>
                            <b>
                                Rp {{ number_format($total_pajak_original, 0, ',', '.') }}
                                @if(str_contains($tax_condition_original, 'Include'))
                                (-)
                                @endif
                            </b>
                        </td>
                    </tr>
                    <tr class="total-row" style="background-color:#e0e0e0;">
                        <td colspan="3" style="text-align:right;"><b>TOTAL BIAYA</b></td>
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

            <!-- Status & Catatan Budget -->
            <table class="details-table">
                <tr>
                    <th style="width: 25%;">Status Budget</th>
                    <td>{{ $status_budget ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Catatan Budget</th>
                    <td>{{ $catatan_budget ?? '-' }}</td>
                </tr>
            </table>

            @php
            // HITUNG NILAI YANG HARUS DIBAYAR KE VENDOR
            $nilaiKePajak = 0;
            $nilaiKeVendor = $total_final; // Default: semua ke vendor

            // Jika ada pajak INCLUDE, kurangi dari pembayaran ke vendor
            if (str_contains($tax_condition_final, 'Include')) {
            $nilaiKeVendor = $total_final - $total_pajak_final;
            $nilaiKePajak = $total_pajak_final;
            }
            // Jika ada pajak EXCLUDE, vendor dapat total nilai barang saja
            elseif (str_contains($tax_condition_final, 'Exclude')) {
            $nilaiKeVendor = $total_nilai_barang_final;
            $nilaiKePajak = $total_pajak_final;
            }
            @endphp

            <table class="details-table payment-details-table">
                <!-- {{-- TOTAL PERINTAH BAYAR --}}
                <tr style="background-color:#e0e0e0;">
                    <td style="width: 30%;"><b>TOTAL PERINTAH BAYAR (FINAL)</b></td>
                    <td><b>Rp {{ number_format($total_final, 0, ',', '.') }}</b></td>
                </tr> -->

                {{-- BREAKDOWN PEMBAYARAN --}}
                <tr style="background-color:#fff3cd;">
                    <td colspan="2" style="text-align:center;"><b>RINCIAN PEMBAYARAN</b></td>
                </tr>

                {{-- PEMBAYARAN KE VENDOR --}}
                <tr>
                    <td style="width: 30%;"><b>Dibayar ke Vendor</b></td>
                    <td>
                        <b style="color: #0066cc;">Rp {{ number_format($nilaiKeVendor, 0, ',', '.') }}</b>
                        @if($nilaiKePajak > 0)
                        <span style="font-size: 10px; color: #666;">
                            @if(str_contains($tax_condition_final, 'Include'))
                            (Total {{ number_format($total_final, 0, ',', '.') }} - Pajak {{ number_format($nilaiKePajak, 0, ',', '.') }})
                            @else
                            (Nilai barang penuh)
                            @endif
                        </span>
                        @endif
                    </td>
                </tr>

                {{-- METODE PEMBAYARAN --}}
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

                {{-- OPSI PEMBAYARAN: BISA DP --}}
                @if($payment_details['opsi_pembayaran'] == 'Bisa DP')
                <tr style="background-color: #e8f4f8;">
                    <td>Pembayaran DP</td>
                    <td>
                        <b>Rp {{ number_format($payment_details['nominal_dp'], 0, ',', '.') }}</b>
                        @if($payment_details['tanggal_dp_aktual'])
                        <span style="color: #166534;">(Telah Dibayar Tgl. {{ $payment_details['tanggal_dp_aktual'] }})</span>
                        @else
                        <span style="color: #d97706;">(Rencana Tgl. {{ $payment_details['tanggal_dp'] }})</span>
                        @endif
                    </td>
                </tr>
                <tr style="background-color: #e8f4f8;">
                    <td>Pelunasan</td>
                    <td>
                        <b>Rp {{ number_format($nilaiKeVendor - $payment_details['nominal_dp'], 0, ',', '.') }}</b>
                        @if($payment_details['tanggal_pelunasan_aktual'])
                        <span style="color: #166534;">(Telah Lunas Tgl. {{ $payment_details['tanggal_pelunasan_aktual'] }})</span>
                        @else
                        <span style="color: #d97706;">(Rencana Tgl. {{ $payment_details['tanggal_pelunasan'] }})</span>
                        @endif
                    </td>
                </tr>

                {{-- OPSI PEMBAYARAN: LANGSUNG LUNAS --}}
                @elseif($payment_details['opsi_pembayaran'] == 'Langsung Lunas')
                <tr style="background-color: #e8f4f8;">
                    <td>Pembayaran Lunas</td>
                    <td>
                        <b>Rp {{ number_format($nilaiKeVendor, 0, ',', '.') }}</b>
                        @if($payment_details['tanggal_pelunasan_aktual'])
                        <span style="color: #166534;">(Telah Lunas Tgl. {{ $payment_details['tanggal_pelunasan_aktual'] }})</span>
                        @else
                        <span style="color: #d97706;">(Rencana Tgl. {{ $payment_details['tanggal_pelunasan'] }})</span>
                        @endif
                    </td>
                </tr>
                @endif

                {{-- PEMBAYARAN PAJAK (JIKA ADA) --}}
                @if($nilaiKePajak > 0)
                <tr style="background-color: #fef3c7;">
                    <td style="width: 30%;">
                        <b>Dibayar untuk Pajak</b><br>
                        <span style="font-size: 10px;">
                            {{ $tax_type_final ?: 'Pajak' }}
                            @if(str_contains($tax_condition_final, 'Include'))
                            (Include - Dipotong dari vendor)
                            @elseif(str_contains($tax_condition_final, 'Exclude'))
                            (Exclude - Ditanggung perusahaan)
                            @endif
                        </span>
                    </td>
                    <td>
                        <b style="color: #dc2626;">Rp {{ number_format($nilaiKePajak, 0, ',', '.') }}</b>
                        @if($payment_details['tanggal_pelunasan_aktual'])
                        <span style="color: #166534;">(Telah Dibayar Tgl. {{ $payment_details['tanggal_pelunasan_aktual'] }})</span>

                        @endif
                    </td>
                </tr>
                @endif

                {{-- TOTAL VALIDASI --}}
                <tr style="background-color:#f9fafb; border-top: 2px solid #000;">
                    <td style="width: 30%;"><b>TOTAL KESELURUHAN</b></td>
                    <td>
                        <b>Rp {{ number_format($nilaiKeVendor + $nilaiKePajak, 0, ',', '.') }}</b>
                        @if($nilaiKePajak > 0)
                        <span style="font-size: 10px; color: #666;">
                            (Vendor: {{ number_format($nilaiKeVendor, 0, ',', '.') }} + Pajak: {{ number_format($nilaiKePajak, 0, ',', '.') }})
                        </span>
                        @endif
                    </td>
                </tr>
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
                    <td style="width:33%; border:none;"></td>
                    <td style="width:33%; border:none;"></td>
                    <td style="width:33%; border:none; text-align:center; vertical-align:top; padding-top:10px;">
                        Depok, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="width:33%; text-align:center; vertical-align: top;">
                        {{-- Tanda tangan Pembayar --}}
                        @if($is_paid && $disbursedByName)
                        <p style="margin-bottom:2px;">Dibayarkan oleh,</p>
                        <p style="margin-bottom:2px;">Staff Accounting</p>
                        <br>
                        @if(!empty($disbursedByQrCode))
                        <img src="{{ $disbursedByQrCode }}" alt="QR Code Verifikasi" style="height: 80px; width: 80px;">
                        @else
                        <br><br><br>
                        @endif
                        <p style="margin-top:5px;"><b><u>{{ $disbursedByName }}</u></b></p>
                        @endif
                    </td>
                    <td style="width:33%; text-align:center; vertical-align: top;">
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
                    <td style="width:33%; text-align:center; vertical-align: top;">
                        <p style="margin-bottom:2px;">Menyetujui,</p>
                        <p style="margin-bottom:2px;">Kepala Divisi Operasional</p>
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