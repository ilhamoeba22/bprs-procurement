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
use Filament\Notifications\Notification;

class BuatPengajuan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static string $view = 'filament.pages.buat-pengajuan';
    protected static ?string $navigationLabel = 'Buat Pengajuan Baru';
    // PERBAIKAN 2: Ubah urutan menu agar di bawah Dashboard
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
                                Select::make('id_user_pemohon')
                                    ->label('Pemohon')
                                    ->options(User::all()->pluck('nama_user', 'id_user'))
                                    ->disabled()
                                    ->required(),
                                TextInput::make('nik')->label('NIK Pemohon')->disabled(),
                                TextInput::make('kantor')->label('Kantor')->disabled(),
                                TextInput::make('divisi')->label('Divisi')->disabled(),
                                TextInput::make('jabatan')->label('Jabatan')->disabled(),
                            ]),
                        ]),
                    Wizard\Step::make('Daftar Barang')
                        ->schema([
                            Repeater::make('items')->schema([
                                Select::make('kategori_barang')->options([
                                    '1a. Software' => '1a. Software',
                                    '1b. Hak Paten' => '1b. Hak Paten',
                                    '1c. Goodwill' => '1c. Goodwill',
                                    '1d. Lainnya (Aktiva Tidak Berwujud)' => '1d. Lainnya (Aktiva Tidak Berwujud)',
                                    '2a. Komputer & Hardware Sistem Informasi' => '2a. Komputer & Hardware Sistem Informasi',
                                    '2b. Peralatan atau Mesin Kantor' => '2b. Peralatan atau Mesin Kantor',
                                    '2c. Kendaraan Bermotor' => '2c. Kendaraan Bermotor',
                                    '2d. Perlengkapan Kantor Lainnya' => '2d. Perlengkapan Kantor Lainnya',
                                    '2e. Lainnya (Aktiva Berwujud)' => '2e. Lainnya (Aktiva Berwujud)',
                                ])->required(),
                                TextInput::make('nama_barang')->required(),
                                TextInput::make('kuantitas')->numeric()->required()->minValue(1),
                                Textarea::make('spesifikasi')->required()->columnSpanFull(),
                                Textarea::make('justifikasi')->required()->columnSpanFull(),
                            ])->columns(2)->columnSpanFull()->addActionLabel('Tambah Barang'),
                        ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $formData = $this->form->getState();

        $pemohon = Auth::user();
        $managerExists = User::where('id_divisi', $pemohon->id_divisi)
            ->where('id_user', '!=', $pemohon->id_user)
            ->whereHas('roles', fn($q) => $q->where('name', 'Manager'))
            ->exists();

        $statusAwal = $managerExists ? Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER : Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV;

        // PERBAIKAN 1: Ambil ID pemohon langsung dari sistem otentikasi
        $pengajuan = Pengajuan::create([
            'kode_pengajuan' => $this->generateKodePengajuan(),
            'id_user_pemohon' => Auth::id(), // Mengambil ID dari user yang login
            'status' => $statusAwal,
        ]);

        if (isset($formData['items'])) {
            $pengajuan->items()->createMany($formData['items']);
        }

        $this->resetForm();

        Notification::make()
            ->title('Pengajuan berhasil dibuat')
            ->body('Pengajuan Anda dengan kode ' . $pengajuan->kode_pengajuan . ' telah dikirim untuk proses persetujuan.')
            ->success()
            ->send();
    }
}
