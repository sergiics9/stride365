<?php

namespace App\Filament\Resources\PublicacionFeeds\Pages;

use App\Filament\Resources\PublicacionFeeds\PublicacionFeedResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicacionFeeds extends ListRecords
{
    protected static string $resource = PublicacionFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
