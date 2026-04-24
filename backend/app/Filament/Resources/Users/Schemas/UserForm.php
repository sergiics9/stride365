<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('club_id')
                    ->relationship('club', 'id')
                    ->required(),
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('apellido')
                    ->required(),
                DatePicker::make('fecha_nacimiento'),
                TextInput::make('sexo')
                    ->default(null),
                TextInput::make('telefono')
                    ->tel()
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('foto_url')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('direccion')
                    ->default(null),
                DatePicker::make('fecha_alta'),
                TextInput::make('estado')
                    ->required()
                    ->default('activo'),
                TextInput::make('stripe_id')
                    ->default(null),
                TextInput::make('pm_type')
                    ->default(null),
                TextInput::make('pm_last_four')
                    ->default(null),
                DateTimePicker::make('trial_ends_at'),
            ]);
    }
}
