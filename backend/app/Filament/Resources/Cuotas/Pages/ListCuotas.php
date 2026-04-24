<?php

namespace App\Filament\Resources\Cuotas\Pages;

use App\Filament\Resources\Cuotas\CuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCuotas extends ListRecords
{
    protected static string $resource = CuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
