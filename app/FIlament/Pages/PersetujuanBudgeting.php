<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea as FormsTextarea;
use Filament\Forms\Components\Select as FormsSelect;
use Filament\Notifications\Notification;
// Impor komponen untuk tampilan detail
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

class PersetujuanBudgeting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.persetujuan-budgeting';
    protected static ?string $navigationLabel = 'Persetujuan Budgeting';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Persetujuan Budgeting)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Tim Budgeting', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->with(['pemohon.divisi', 'items'])
            ->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')->form([ /* ... Form Detail Tanpa Pemohon ... */]),

            Action::make('submit_budget_review')
                ->label('Submit Review Budget')->color('primary')->icon('heroicon-o-pencil-square')
                ->form([
                    FormsSelect::make('budget_status')
                        ->label('Status Ketersediaan Budget')
                        ->options([
                            'Budget Tersedia' => 'Budget Tersedia',
                            'Budget Habis' => 'Budget Habis',
                            'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB',
                        ])->required(),
                    FormsTextarea::make('budget_catatan')->label('Catatan Tim Budgeting (Opsional)'),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    // === PERBAIKAN LOGIKA DI SINI ===
                    // Tentukan alur selanjutnya berdasarkan nominal, TIDAK peduli status budgetnya.
                    $newStatus = $record->total_nilai <= 1000000
                        ? Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA
                        : Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKSI;

                    // Simpan konfirmasi budget dan lanjutkan alur ke approver selanjutnya
                    $record->update([
                        'budget_status' => $data['budget_status'],
                        'budget_catatan' => $data['budget_catatan'],
                        'status' => $newStatus,
                    ]);
                    Notification::make()->title('Review budget berhasil disubmit')->success()->send();
                }),
        ];
    }
}
