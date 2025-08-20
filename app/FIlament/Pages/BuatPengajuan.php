<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Pengajuan;
use App\Models\User;
use App\Models\Jabatan;
use Filament\Notifications\Notification;

class BuatPengajuan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static string $view = 'filament.pages.buat-pengajuan';
    protected static ?string $navigationLabel = 'Buat Pengajuan Baru';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    private function generateKodePengajuan(): string
    {
        $user = Auth::user()->load('kantor');
        $kodeKantor = $user->kantor?->kode_kantor ?? 'XXX';
        $nik = $user->nik_user;
        $date = now()->format('Ymd');

        $todayCount = Pengajuan::where('id_user_pemohon', $user->id_user)
            ->whereDate('created_at', today())
            ->count();

        $sequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

        return "REQ/{$kodeKantor}/{$nik}/{$date}/{$sequence}";
    }

    private function resetForm(): void
    {
        $user = Auth::user()->load(['kantor', 'divisi', 'jabatan']);
        $this->form->fill([
            'id_user_pemohon' => $user->id_user,
            'kode_pengajuan' => $this->generateKodePengajuan(),
            'nik' => $user->nik_user,
            'kantor' => $user->kantor?->nama_kantor,
            'divisi' => $user->divisi?->nama_divisi,
            'jabatan' => $user->jabatan?->nama_jabatan,
            'items' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled()->required(),
                                Select::make('id_user_pemohon')->label('Pemohon')->options(User::all()->pluck('nama_user', 'id_user'))->disabled()->required(),
                                TextInput::make('nik')->label('NIK Pemohon')->disabled(),
                                TextInput::make('kantor')->label('Kantor')->disabled(),
                                TextInput::make('divisi')->label('Divisi')->disabled(),
                                TextInput::make('jabatan')->label('Jabatan')->disabled(),
                            ]),
                        ]),
                    Wizard\Step::make('Detail Pengajuan Barang')
                        ->schema([
                            Repeater::make('items')->label('')
                                ->schema([
                                    Select::make('kategori_barang')->label('Kategori')->options([
                                        'Barang IT' => 'Barang IT',
                                        'Barang Non-IT' => 'Barang Non-IT',
                                        'Jasa' => 'Jasa',
                                        'Sewa' => 'Sewa',
                                    ])->required(),
                                    TextInput::make('nama_barang')->label('Nama')->required(),
                                    TextInput::make('kuantitas')->numeric()->required()->minValue(1),
                                    Textarea::make('spesifikasi')->required()->columnSpanFull(),
                                    Textarea::make('justifikasi')->required()->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull()
                                ->addActionLabel('Tambah Barang')
                                ->minItems(1)
                                ->required()
                                ->validationMessages([
                                    'min' => 'Harap tambahkan minimal satu barang untuk diajukan.',
                                    'required' => 'Harap tambahkan minimal satu barang untuk diajukan.',
                                ]),
                        ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        // 1. Ambil semua data dari form
        $formData = $this->form->getState();
        $pemohon = Auth::user();
        $statusAwal = ''; // Inisialisasi status awal

        // 2. Definisikan peran-peran pimpinan yang bisa melewati approval awal
        $pimpinanRoles = [
            'Direktur Utama',
            'Direktur Operasional',
            'Kepala Divisi',
            'Kepala Divisi IT',
            'Kepala Divisi GA',
            'Kepala Divisi Operasional'
        ];

        // 3. Cek apakah pemohon adalah seorang Pimpinan
        if ($pemohon->hasAnyRole($pimpinanRoles)) {
            // Jika YA, maka lewati approval Manager/Kadiv
            $needsITRecommendation = false;
            if (isset($formData['items'])) {
                foreach ($formData['items'] as $item) {
                    // Cek apakah ada barang yang terkait IT
                    if ($item['kategori_barang'] === 'Barang IT') {
                        $needsITRecommendation = true;
                        break;
                    }
                }
            }
            // Tentukan alur selanjutnya: ke IT atau langsung ke GA
            $statusAwal = $needsITRecommendation ? Pengajuan::STATUS_REKOMENDASI_IT : Pengajuan::STATUS_SURVEI_GA;
        } else {
            // Jika BUKAN Pimpinan, jalankan alur persetujuan normal
            $jabatanPemohon = Jabatan::find($pemohon->id_jabatan);
            $hasManagerAsDirectSuperior = false;

            // Cek siapa atasan langsung dari pemohon
            if ($jabatanPemohon && $jabatanPemohon->acc_jabatan_id) {
                $atasanUser = User::where('id_jabatan', $jabatanPemohon->acc_jabatan_id)->first();
                if ($atasanUser && $atasanUser->hasRole('Manager')) {
                    $hasManagerAsDirectSuperior = true;
                }
            }

            // Tentukan status awal berdasarkan jabatan atasan
            $statusAwal = $hasManagerAsDirectSuperior
                ? Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER
                : Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV;
        }

        // 4. Buat record Pengajuan utama di database
        $pengajuan = Pengajuan::create([
            'kode_pengajuan' => $this->generateKodePengajuan(),
            'id_user_pemohon' => Auth::id(),
            'status' => $statusAwal,
        ]);

        // 5. Buat record untuk setiap item barang yang diajukan
        if (isset($formData['items'])) {
            $pengajuan->items()->createMany($formData['items']);
        }

        // 6. Kosongkan form setelah berhasil
        $this->resetForm();

        // 7. Tampilkan notifikasi sukses
        Notification::make()
            ->title('Pengajuan berhasil dibuat')
            ->body('Pengajuan Anda dengan kode ' . $pengajuan->kode_pengajuan . ' telah dikirim untuk proses persetujuan.')
            ->success()
            ->send();
    }
}
