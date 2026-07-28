<?php

namespace App\Filament\Resources\AllPengajuanUserResource\Pages;

use App\Filament\Resources\AllPengajuanUserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllPengajuanUser extends EditRecord
{
    protected static string $resource = AllPengajuanUserResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
