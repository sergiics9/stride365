<?php

namespace App\Filament\Resources\PublicacionFeeds\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PublicacionFeedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('titulo')
                    ->default(null),
                Textarea::make('contenido')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('imagen_url')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('tipo')
                    ->default(null),
                TextInput::make('visibilidad')
                    ->default(null),
                DateTimePicker::make('fecha_publicacion')
                    ->required(),
                TextInput::make('estado')
                    ->required()
                    ->default('activo'),
            ]);
    }
}
