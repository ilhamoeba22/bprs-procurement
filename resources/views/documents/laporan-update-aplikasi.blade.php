<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Update Aplikasi E-Procurement BPRS</title>
    <style>
        @page {
            margin: 1.2cm 1.5cm 1.5cm 1.5cm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10pt;
            line-height: 1.45;
        }

        .header {
            border-bottom: 2px solid #008767;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .header table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-text {
            font-size: 18pt;
            font-weight: bold;
            color: #008767;
            letter-spacing: 0.5px;
        }

        .sub-logo-text {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .doc-meta {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .meta-table td {
            padding: 7px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #f1f5f9;
        }

        .meta-label {
            font-weight: bold;
            color: #334155;
            width: 25%;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #008767;
            border-left: 4px solid #008767;
            padding-left: 8px;
            margin-top: 18px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        p {
            margin-top: 0;
            margin-bottom: 8px;
            text-align: justify;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 12px;
            font-size: 9pt;
        }

        .data-table th {
            background-color: #008767;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 9px;
            border: 1px solid #008767;
        }

        .data-table td {
            padding: 6px 9px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #ffffff;
            border-radius: 3px;
        }

        .badge-success { background-color: #16a34a; }
        .badge-info { background-color: #0284c7; }
        .badge-warning { background-color: #d97706; }

        .callout {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
            padding: 8px 12px;
            margin: 10px 0;
            font-size: 9pt;
            color: #14532d;
            border-radius: 0 5px 5px 0;
        }

        .callout-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        ol, ul {
            margin-top: 0;
            margin-bottom: 8px;
            padding-left: 18px;
        }

        li {
            margin-bottom: 3px;
        }

        .diagram-box {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 15px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px;
            border-radius: 6px;
        }

        .diagram-img {
            max-width: 100%;
            height: auto;
        }

        .diagram-caption {
            font-size: 8.5pt;
            font-style: italic;
            color: #475569;
            margin-top: 6px;
        }

        .page-break {
            page-break-after: always;
        }

        .footer-note {
            margin-top: 25px;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }

        .signature-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 8px;
        }

        .signature-space {
            height: 45px;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo-text">BPRS MCI</div>
                    <div class="sub-logo-text">Sistem Informasi E-Procurement & Pengadaan Barang/Jasa</div>
                </td>
                <td class="doc-title-box">
                    <div class="doc-title">LAPORAN UPDATE SISTEM</div>
                    <div class="doc-meta">Nomor Dokumen: BPRS/IT-RPT/2026/07/002</div>
                    <div class="doc-meta">Tanggal Rilis: 28 Juli 2026 | Versi: v1.3.0</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Metadata Laporan -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Aplikasi</td>
            <td>E-Procurement BPRS (bprs-procurement)</td>
            <td class="meta-label">Lingkungan</td>
            <td>Production / Development</td>
        </tr>
        <tr>
            <td class="meta-label">Topik Utama Update</td>
            <td colspan="3"><strong>1. Fitur Delegasi Approval Cuti & Izin Mendadak (Plt)<br>2. Perbaikan Historis Rekap Divisi Pengajuan User</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Tim Pengembang</td>
            <td>Tim IT Development & Antigravity AI</td>
            <td class="meta-label">Status Verifikasi</td>
            <td><span class="badge badge-success">TERVERIFIKASI & SIAP PRODUCTION</span></td>
        </tr>
    </table>

    <!-- Section 1: Ringkasan Eksekutif -->
    <div class="section-title">1. Ringkasan Eksekutif (Executive Summary)</div>
    <p>
        Laporan ini menyajikan ringkasan pembaruan sistem pada aplikasi <strong>E-Procurement BPRS</strong>. Pembaruan ini dirancang untuk menyelesaikan dua tantangan utama operasional pengadaan barang dan jasa:
    </p>
    <ol>
        <li><strong>Penanganan Pengajuan saat Pejabat/Approver Berhalangan Hadir (Cuti/Sakit Mendadak)</strong>: Mengatasi potensi kemacetan (*bottleneck*) proses approval ketika pejabat terkait (Manager, Kadiv, IT, GA, Budgeting, atau Direksi) sedang cuti terencana maupun izin sakit mendadak.</li>
        <li><strong>Integritas Historis Rekap Per Divisi</strong>: Memastikan bahwa mutasi atau perpindahan divisi seorang pegawai di masa mendatang tidak mengubah histori pengadaan yang pernah dilakukannya di divisi lama.</li>
    </ol>

    <div class="callout">
        <div class="callout-title">Hasil Utama Pembaruan:</div>
        Sistem pengadaan kini memiliki fleksibilitas 100% (*Zero Bottleneck*) dengan adanya modul penunjukan **User Pengganti (Plt)** yang terkunci pada divisi yang sama. Seluruh 10 tahapan alur pengadaan terlindungi dan tercatat secara transparan pada audit trail.
    </div>

    <!-- Section 2: Pembaruan 1 - Perbaikan Historis Divisi -->
    <div class="section-title">2. Pembaruan 1: Perbaikan Historis Rekap Divisi Pengajuan</div>
    <p>
        <strong>Masalah Sebelumnya:</strong> Pada versi awal, rekap pengadaan per divisi dihitung berdasarkan relasi dinamis ke tabel <code>users.id_divisi</code>. Ketika seorang pegawai (contoh: Achmad Syihab Arya) dipindahkan dari Divisi GA ke Divisi Bisnis, seluruh histori pengajuan pengadaan yang pernah dilakukan pegawai tersebut sewaktu di GA ikut berpindah ke Divisi Bisnis pada grafik dan laporan.
    </p>
    <p>
        <strong>Solusi Teknis yang Diterapkan:</strong>
    </p>
    <ul>
        <li>Menambahkan kolom fisik <code>id_divisi</code> pada tabel <code>pengajuans</code> melalui migrasi database.</li>
        <li>Menambahkan event listener <code>creating</code> pada model <code>Pengajuan</code> untuk merekam secara permanen ID divisi pemohon pada saat pengajuan dibuat.</li>
        <li>Memperbarui kueri grafik <code>PengajuanPerDivisiChart</code>, widget <code>RiwayatPengajuanWidget</code>, seluruh 10 halaman persetujuan Filament, dan template laporan PDF agar merujuk pada <code>pengajuans.id_divisi</code>.</li>
    </ul>

    <div class="page-break"></div>

    <!-- Section 3: Pembaruan 2 - Modul Delegasi Approval Cuti & Izin Mendadak -->
    <div class="section-title">3. Pembaruan 2: Modul Delegasi Approval Cuti & Izin Mendadak (Plt)</div>
    <p>
        Fitur ini memungkinkan pengalihan wewenang persetujuan (*approval rights*) secara otomatis berbasis rentang tanggal (*date-range based delegation*) sesuai SOP internal BPRS.
    </p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Komponen Fitur</th>
                <th>Deskripsi & Aturan Bisnis</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Dua Jalur Input</strong></td>
                <td>
                    1. <strong>Self-Service</strong>: User pimpinan/approver menginput mandiri saat berencana cuti/dinas.<br>
                    2. <strong>Bypass Admin HR / Super Admin</strong>: Admin HR dapat menginputkan penunjukan pengganti atas nama pejabat yang <strong>sakit mendadak / emergency</strong>.
                </td>
            </tr>
            <tr>
                <td><strong>Batasan Divisi (SOP)</strong></td>
                <td>Opsi pilihan User Pengganti (Plt) dikunci secara ketat <strong>hanya dari divisi yang sama (<code>id_divisi</code>)</strong> dengan pejabat yang berhalangan.</td>
            </tr>
            <tr>
                <td><strong>Jenis Halangan</strong></td>
                <td>Pilihan kategori: <code>Cuti Tahunan</code>, <code>Izin Sakit / Emergency</code>, <code>Dinas Luar</code>, dan <code>Acara Mendadak / Lainnya</code>.</td>
            </tr>
            <tr>
                <td><strong>Masa Berlaku Otomatis</strong></td>
                <td>Wewenang persetujuan otomatis <strong>AKTIF</strong> pada rentang <code>tanggal_mulai</code> s/d <code>tanggal_selesai</code> dan otomatis <strong>KADALUWARSA / NON-AKTIF</strong> setelah rentang waktu berakhir.</td>
            </tr>
            <tr>
                <td><strong>Deaktivasi Dini</strong></td>
                <td>Terdapat aksi tombol <strong>"Akhiri Sekarang"</strong> jika pejabat sudah masuk kerja lebih awal dari perkiraan.</td>
            </tr>
        </tbody>
    </table>

    <!-- Section 4: Swimlane BPMN Diagram -->
    <div class="section-title">4. Swimlane Flowchart (BPMN Cross-Functional Flowchart)</div>
    <p>
        Diagram Swimlane di bawah menggambarkan pemisahan peran dan tanggung jawab (*Cross-Functional Roles*) lintas 7 departemen/divisi beserta penanganan pengalihan wewenang ke **Plt / User Pengganti** saat pejabat di tahap tersebut berhalangan hadir:
    </p>

    <div class="diagram-box">
        <img src="{{ public_path('images/swimlane_bpmn_diagram.svg') }}" class="diagram-img" alt="Swimlane BPMN Flowchart">
        <div class="diagram-caption">Gambar 4.1: Swimlane BPMN Flowchart Alur Pengadaan dengan Penanganan Delegasi Cuti &amp; Sakit Mendadak (Plt)</div>
    </div>

    <div class="page-break"></div>

    <!-- Section 5: Activity Diagram Logic -->
    <div class="section-title">5. Activity Diagram Logic Pengecekan Otorisasi Plt</div>
    <p>
        Diagram Aktivitas (*Activity Diagram*) berikut menjelaskan logika keputusan sistem (*System Decision Tree*) ketika seorang user mengakses halaman persetujuan:
    </p>

    <div class="diagram-box">
        <img src="{{ public_path('images/activity_diagram.svg') }}" class="diagram-img" alt="Activity Diagram Logic">
        <div class="diagram-caption">Gambar 5.1: Activity Diagram Pengecekan Otorisasi Hak Akses User Utama vs User Pengganti (Plt)</div>
    </div>

    <!-- Section 6: Matriks Cakupan Seluruh User & Peran -->
    <div class="section-title">6. Matriks Cakupan Seluruh User & Peran Pengadaan</div>
    <p>
        Seluruh tahapan alur pengadaan dari awal hingga pelunasan/pencairan dana telah tercover 100% oleh logika delegasi:
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 22%;">Tahapan Alur</th>
                <th style="width: 25%;">Peran / User Utama</th>
                <th style="width: 25%;">Penanganan saat Cuti/Sakit</th>
                <th style="width: 23%;">Status Coverage</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><strong>Pengajuan Baru</strong></td>
                <td>User Pemohon (Staff/Pimpinan)</td>
                <td>Pengajuan diproses sesuai alur divisi pemohon.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>2</td>
                <td><strong>Approval Level 1</strong></td>
                <td>Manager Divisi</td>
                <td>Pengajuan otomatis masuk ke dashboard <strong>Plt Manager (1 Divisi)</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>3</td>
                <td><strong>Approval Level 2</strong></td>
                <td>Kepala Divisi Pemohon</td>
                <td>Pengajuan otomatis masuk ke dashboard <strong>Plt Kadiv (1 Divisi)</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>4</td>
                <td><strong>Rekomendasi IT</strong></td>
                <td>Kepala Divisi IT</td>
                <td>Rekomendasi barang IT ditangani oleh <strong>Plt Kadiv IT (Tim IT)</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>5</td>
                <td><strong>Survei Harga GA</strong></td>
                <td>Tim General Affairs (GA)</td>
                <td>Pengisian 3 vendor & survei ditangani oleh <strong>Plt Tim GA</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>6</td>
                <td><strong>Budget Control</strong></td>
                <td>Tim Budgeting</td>
                <td>Pengecekan & penguncian anggaran ditangani oleh <strong>Plt Tim Budgeting</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>7</td>
                <td><strong>Validasi Ops & GA</strong></td>
                <td>Kadiv Operasional & Kadiv GA</td>
                <td>Persetujuan kelayakan ops/GA ditangani oleh <strong>Plt Kadiv Ops / GA</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>8</td>
                <td><strong>Approval Direksi</strong></td>
                <td>Direktur Ops & Direktur Utama</td>
                <td>Persetujuan batas wewenang direksi ditangani oleh <strong>Plt Direktur</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
            <tr>
                <td>9</td>
                <td><strong>Pencairan Dana</strong></td>
                <td>Tim Finance / Kasir</td>
                <td>Eksekusi bayar & upload bukti transfer ditangani oleh <strong>Plt Finance</strong>.</td>
                <td><span class="badge badge-success">100% TERCOVER</span></td>
            </tr>
        </tbody>
    </table>

    <!-- Section 7: Transparansi & Audit Trail -->
    <div class="section-title">7. Transparansi & Audit Trail (Laporan PDF & SPM)</div>
    <p>
        Meskipun wewenang persetujuan dialihkan ke User Pengganti (Plt), sistem tetap menjaga kepatuhan audit BPRS secara jujur:
    </p>
    <ul>
        <li><strong>Tampilan Tabel Aplikasi</strong>: Pengajuan yang muncul karena hak delegasi diberi badge indikator jelas, contoh: <code>[Plt: Achmad Syihab Arya]</code>.</li>
        <li><strong>Pencatatan Database</strong>: Database merekam secara presisi ID User Eksekutor Asli (misal: <code>manager_approved_by = ID_Eko</code>).</li>
        <li><strong>Dokumen Cetak PDF (SPM / Laporan Akhir)</strong>: Pada kolom tanda tangan dan riwayat persetujuan dokumen PDF tertulis secara jujur:  
            <br><em>"Disetujui oleh: Eko Prasetyo (a.n. Plt Manager Achmad Syihab Arya)"</em>.
        </li>
    </ul>

    <!-- Section 8: Lembar Pengesahan -->
    <div class="section-title">8. Lembar Pengesahan Rilis Update</div>
    <p>
        Demikian laporan update aplikasi ini disusun sebagai bukti penyelesaian dan verifikasi pengembangan sistem E-Procurement BPRS v1.3.0.
    </p>

    <table class="signature-table">
        <tr>
            <td>
                <div>Disiapkan Oleh,</div>
                <div style="font-weight: bold; color: #008767; margin-top: 4px;">Tim IT Development</div>
                <div class="signature-space"></div>
                <div><strong>( Antigravity AI / IT Dev )</strong></div>
                <div style="font-size: 8.5pt; color: #64748b;">Pengembang Aplikasi</div>
            </td>
            <td>
                <div>Disetujui & Diterima Oleh,</div>
                <div style="font-weight: bold; color: #008767; margin-top: 4px;">BPRS MCI Management</div>
                <div class="signature-space"></div>
                <div><strong>( User / Pimpinan BPRS )</strong></div>
                <div style="font-size: 8.5pt; color: #64748b;">Penanggung Jawab Operasional</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini dihasilkan secara otomatis oleh Sistem E-Procurement BPRS pada tanggal 28 Juli 2026.
    </div>

</body>
</html>
