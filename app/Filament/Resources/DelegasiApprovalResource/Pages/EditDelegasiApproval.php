<?php

namespace App\Filament\Resources\DelegasiApprovalResource\Pages;

use App\Filament\Resources\DelegasiApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDelegasiApproval extends EditRecord
{
    protected static string $resource = DelegasiApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
