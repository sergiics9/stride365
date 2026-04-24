<?php

namespace App\Filament\Resources\Cuotas;

use App\Filament\Resources\Cuotas\Pages\CreateCuota;
use App\Filament\Resources\Cuotas\Pages\EditCuota;
use App\Filament\Resources\Cuotas\Pages\ListCuotas;
use App\Filament\Resources\Cuotas\Schemas\CuotaForm;
use App\Filament\Resources\Cuotas\Tables\CuotasTable;
use App\Models\Cuota;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CuotaResource extends Resource
{
    protected static ?string $model = Cuota::class;

    protected static ?string $modelLabel = 'Cuota';

    protected static ?string $pluralModelLabel = 'Cuotas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return CuotaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CuotasTable::configure($table);
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
            'index' => ListCuotas::route('/'),
            'create' => CreateCuota::route('/create'),
            'edit' => EditCuota::route('/{record}/edit'),
        ];
    }
}
