<?php

namespace App\Filament\Resources\PublicacionFeeds\Pages;

use App\Filament\Resources\PublicacionFeeds\PublicacionFeedResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicacionFeed extends EditRecord
{
    protected static string $resource = PublicacionFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
