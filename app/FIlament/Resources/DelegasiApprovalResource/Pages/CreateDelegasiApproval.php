<?php

namespace App\Filament\Resources\DelegasiApprovalResource\Pages;

use App\Filament\Resources\DelegasiApprovalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDelegasiApproval extends CreateRecord
{
    protected static string $resource = DelegasiApprovalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
