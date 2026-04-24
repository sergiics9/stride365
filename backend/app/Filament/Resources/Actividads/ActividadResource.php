<?php

namespace App\Filament\Resources\Actividads;

use App\Filament\Resources\Actividads\Pages\CreateActividad;
use App\Filament\Resources\Actividads\Pages\EditActividad;
use App\Filament\Resources\Actividads\Pages\ListActividads;
use App\Filament\Resources\Actividads\Schemas\ActividadForm;
use App\Filament\Resources\Actividads\Tables\ActividadsTable;
use App\Models\Actividad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActividadResource extends Resource
{
    protected static ?string $model = Actividad::class;

    protected static ?string $modelLabel = 'Actividad';

    protected static ?string $pluralModelLabel = 'Actividades';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return ActividadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActividadsTable::configure($table);
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
            'index' => ListActividads::route('/'),
            'create' => CreateActividad::route('/create'),
            'edit' => EditActividad::route('/{record}/edit'),
        ];
    }
}
