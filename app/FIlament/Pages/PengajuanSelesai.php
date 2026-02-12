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

class PengajuanSelesai extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static string $view = 'filament.pages.pengajuan-selesai';
    protected static ?string $navigationLabel = 'Pengajuan Selesai';
    protected static ?int $navigationSort = 7;
    protected static ?string $slug = 'pengajuan-selesai';

    public function getTitle(): string
    {
        return 'Daftar Pengajuan Selesai';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['General Affairs', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()
            ->with(['pemohon.divisi', 'items', 'items.surveiHargas', 'vendorPembayaran'])
            ->where('status', Pengajuan::STATUS_SELESAI)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            TextColumn::make('nama_barang')->label('Nama Barang')->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereHas('items', function (Builder $q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%");
                });
            })->getStateUsing(function (Pengajuan $record): string {
                $firstItem = $record->items->first();
                return $firstItem ? $firstItem->nama_barang : '-';
            }),
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->money('IDR')
                ->sortable(),
            BadgeColumn::make('status')
                ->label('Status')
                ->color('success'),
            TextColumn::make('updated_at')->label('Selesai Pada')->date(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading(fn(Pengajuan $record): string => "Detail Pengajuan {$record->kode_pengajuan}")
                ->modalWidth('4xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                     // Reuse the mount logic from SurveiHargaGA or similar if needed, 
                     // or just rely on relationship loading if StandardDetailSections handles it.
                     // For simplicity, we'll load key relationships here.
                    $record->load([
                        'items.surveiHargas.revisiHargas.direvisiOleh',
                        'vendorPembayaran',
                        'pemohon.divisi',
                    ]);
                    $form->fill($record->toArray());
                })
                ->form([
                    ...StandardDetailSections::make(),
                    RevisiTimelineSection::make(),
                ]),
        ];
    }
}
