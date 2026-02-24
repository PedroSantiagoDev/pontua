<?php

namespace App\Filament\Resources\Holidays\Schemas;

use App\Enums\HolidayScope;
use App\Enums\HolidayType;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('date')
                    ->label('Data')
                    ->required()
                    ->displayFormat('d/m/Y'),

                Select::make('type')
                    ->label('Tipo')
                    ->options(HolidayType::class)
                    ->required()
                    ->live(),

                Toggle::make('recurrent')
                    ->label('Recorrente (repete todo ano)')
                    ->default(false),

                Select::make('scope')
                    ->label('Abrangência')
                    ->options(HolidayScope::class)
                    ->default(HolidayScope::All->value)
                    ->required()
                    ->live(),

                Repeater::make('employeeHolidays')
                    ->label('Colaboradores Dispensados')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Colaborador')
                            ->options(Employee::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('reason')
                            ->label('Motivo da dispensa')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('scope') === HolidayScope::Partial->value)
                    ->minItems(1)
                    ->defaultItems(1),
            ]);
    }
}
