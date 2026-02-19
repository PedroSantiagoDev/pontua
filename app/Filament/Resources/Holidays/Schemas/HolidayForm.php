<?php

namespace App\Filament\Resources\Holidays\Schemas;

use App\Enums\TipoFeriado;
use App\Models\Holiday;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Data')
                    ->required()
                    ->unique(Holiday::class, 'date', ignoreRecord: true),
                TextInput::make('description')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Tipo')
                    ->options(TipoFeriado::class)
                    ->default(TipoFeriado::National)
                    ->required(),
            ]);
    }
}
