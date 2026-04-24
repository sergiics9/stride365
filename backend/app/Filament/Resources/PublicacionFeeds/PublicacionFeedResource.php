<?php

namespace App\Filament\Resources\PublicacionFeeds;

use App\Filament\Resources\PublicacionFeeds\Pages\CreatePublicacionFeed;
use App\Filament\Resources\PublicacionFeeds\Pages\EditPublicacionFeed;
use App\Filament\Resources\PublicacionFeeds\Pages\ListPublicacionFeeds;
use App\Filament\Resources\PublicacionFeeds\Schemas\PublicacionFeedForm;
use App\Filament\Resources\PublicacionFeeds\Tables\PublicacionFeedsTable;
use App\Models\PublicacionFeed;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PublicacionFeedResource extends Resource
{
    protected static ?string $model = PublicacionFeed::class;

    protected static ?string $modelLabel = 'Publicación';

    protected static ?string $pluralModelLabel = 'Publicaciones del Feed';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PublicacionFeedForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PublicacionFeedsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicacionFeeds::route('/'),
            'create' => CreatePublicacionFeed::route('/create'),
            'edit' => EditPublicacionFeed::route('/{record}/edit'),
        ];
    }
}
