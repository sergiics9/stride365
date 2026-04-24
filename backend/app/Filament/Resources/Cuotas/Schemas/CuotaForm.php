<?php

namespace App\Filament\Resources\Cuotas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CuotaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('periodo')
                    ->default(null),
                TextInput::make('concepto')
                    ->default(null),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                DatePicker::make('fecha_vencimiento')
                    ->required(),
                TextInput::make('estado')
                    ->required()
                    ->default('pendiente'),
            ]);
    }
}
