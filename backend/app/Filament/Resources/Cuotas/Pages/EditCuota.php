<?php

namespace App\Filament\Resources\Cuotas\Pages;

use App\Filament\Resources\Cuotas\CuotaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCuota extends EditRecord
{
    protected static string $resource = CuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
