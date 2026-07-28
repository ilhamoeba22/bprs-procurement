<?php

namespace App\Filament\Resources\AllPengajuanUserResource\Pages;

use App\Filament\Resources\AllPengajuanUserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAllPengajuanUser extends CreateRecord
{
    protected static string $resource = AllPengajuanUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['kode_pengajuan'] = 'PGJ/' . date('Ymd') . '/' . Str::upper(Str::random(5));
        return $data;
    }
}
