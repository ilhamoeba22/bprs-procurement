<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;

class PersetujuanKadivOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.pages.persetujuan-kadiv-operasional';
    protected static ?string $navigationLabel = 'Validasi Budget (Kadiv Ops)';
    protected static ?int $navigationSort = 8;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Validasi Budget Kadiv Operasional)';
    }

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['Kepala Divisi Operasional', 'Super Admin']);
    }


    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas.revisiHargas']);

        $statuses = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
        ];

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statuses) {
                $q->whereIn('status', $statuses)
                    ->orWhere('kadiv_ops_budget_approved_by', $user->id_user);
            });
        } else {
            $statusesAfter = [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
            ];
            $query->whereIn('status', array_merge($statuses, $statusesAfter))
                ->orWhereNotNull('kadiv_ops_budget_approved_by');
        }

        return $query->latest();
    }


    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if (in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                    ])) {
                        return 'Menunggu Validasi Anda';
                    }
                    return 'Sudah Divalidasi';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Divalidasi' => 'success',
                    'Menunggu Validasi Anda' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('Detail Pengajuan dan Review Budget')
                ->modalWidth('5xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load([
                        'items.surveiHargas.revisiHargas.revisiBudgetApprover',
                        'items.vendorFinal',
                        'pemohon.divisi',
                        'approverBudget'
                    ]);
                    $formData = $record->toArray();

                    // Siapkan data Revisi
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;
                    if ($revisi) {
                        $formData['revisi_data'] = $revisi->toArray();
                    }

                    // Siapkan data Vendor Final
                    $formData['items_with_final_vendor'] = $record->items->filter(fn($item) => $item->vendorFinal)
                        ->map(fn($item) => [
                            'nama_barang' => $item->nama_barang,
                            'nama_vendor' => $item->vendorFinal->nama_vendor,
                            'harga' => $item->vendorFinal->harga,
                            'metode_pembayaran' => $item->vendorFinal->metode_pembayaran,
                            'opsi_pembayaran' => $item->vendorFinal->opsi_pembayaran,
                        ])->values()->toArray();

                    $form->fill($formData);
                })
                ->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('pemohon.nama_user')->label('Pemohon')->disabled(),
                            TextInput::make('status')->disabled(),
                        ]),
                    ])->collapsible()->collapsed(),

                    Section::make('Vendor & Harga Final yang Disetujui')->schema([
                        Repeater::make('items_with_final_vendor')->label('')->schema([
                            Grid::make(5)->schema([
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                TextInput::make('nama_vendor')->label('Nama Vendor')->disabled(),
                                TextInput::make('harga')->label('Harga Satuan')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                                TextInput::make('metode_pembayaran')->label('Metode Bayar')->disabled(),
                                TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->disabled(),
                            ]),
                        ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                    ])->collapsible()->collapsed()->visible(fn($get) => !empty($get('items_with_final_vendor'))),

                    Section::make('Detail Revisi & Review Budget Ulang')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('revisi_data.harga_revisi')->label('Harga Setelah Revisi')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                                TextInput::make('revisi_data.direvisi_oleh.nama_user')->label('Harga Direvisi Oleh')->disabled(),
                                TextInput::make('revisi_data.tanggal_revisi')->label('Tanggal Revisi')->disabled(),
                            ]),
                            Textarea::make('revisi_data.alasan_revisi')->label('Alasan Revisi Harga')->disabled(),
                            Placeholder::make('hr')->content(new HtmlString('<hr class="my-4"/>')),
                            Grid::make(2)->schema([
                                TextInput::make('revisi_data.revisi_budget_status_pengadaan')->label('Status Budget Revisi')->disabled(),
                                Textarea::make('revisi_data.revisi_budget_catatan_pengadaan')->label('Catatan Budget Revisi')->disabled(),
                                TextInput::make('revisi_data.revisi_budget_approver.nama_user')->label('Budget Revisi Direview Oleh')->disabled(),
                            ]),
                        ])
                        ->collapsible()->collapsed()
                        ->visible(fn($get) => !empty($get('revisi_data'))),

                    Section::make('Review Budget Awal')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            TextInput::make('approverBudget.nama_user')->label('Direview Oleh')->disabled(),
                        ]),
                    ])->visible(fn($get) => !empty($get('budget_status_pengadaan'))),
                ]),

            Action::make('confirm_budget')
                ->label('Konfirmasi Sesuai')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Review Budget')
                ->modalDescription('Apakah Anda yakin review budget dari tim Budgeting sudah sesuai?')
                ->action(function (Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        'kadiv_ops_budget_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Review budget telah dikonfirmasi.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                ])),
        ];
    }
}
