<?php

namespace App\Filament\Resources\DelegasiApprovalResource\Pages;

use App\Filament\Resources\DelegasiApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDelegasiApprovals extends ListRecords
{
    protected static string $resource = DelegasiApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Delegasi / Form Cuti'),
        ];
    }
}
