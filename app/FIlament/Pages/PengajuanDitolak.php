<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Form;
use App\Filament\Components\StandardDetailSections;
use App\Filament\Components\RevisiTimelineSection;

class PengajuanDitolak extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-x-circle';
    protected static string $view = 'filament.pages.pengajuan-ditolak';
    protected static ?string $navigationLabel = 'Pengajuan Ditolak';
    protected static ?int $navigationSort = 8;
    protected static ?string $slug = 'pengajuan-ditolak';

    public function getTitle(): string
    {
        return 'Daftar Pengajuan Ditolak';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['General Affairs', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()
            ->with(['pemohon.divisi', 'items'])
            ->where(function (Builder $query) {
                $query->whereIn('status', [
                    Pengajuan::STATUS_DITOLAK_MANAGER,
                    Pengajuan::STATUS_DITOLAK_KADIV,
                    Pengajuan::STATUS_DITOLAK_KADIV_GA,
                    Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                    Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                    Pengajuan::STATUS_DITOLAK_KADIV_OPS,
                ])->orWhere('status', 'like', '%Ditolak%');
            })
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            TextColumn::make('nama_barang')->label('Nama Barang')->getStateUsing(function (Pengajuan $record): string {
                $firstItem = $record->items->first();
                return $firstItem ? $firstItem->nama_barang : '-';
            }),
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->money('IDR')
                ->sortable(),
            BadgeColumn::make('status')
                ->label('Status')
                ->color('danger'),
            TextColumn::make('updated_at')->label('Ditolak Pada')->date(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading(fn(Pengajuan $record): string => "Detail Pengajuan {$record->kode_pengajuan}")
                ->modalWidth('4xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                    $record->load([
                        'items',
                        'pemohon.divisi',
                    ]);
                    $form->fill($record->toArray());
                })
                ->form([
                    ...StandardDetailSections::make(),
                    // Timeline might not be fully populated for rejected items early on, but safe to include
                ]),
        ];
    }
}
