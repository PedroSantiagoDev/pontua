<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\Turno;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('registration_number')
                    ->label('Matrícula')
                    ->required()
                    ->maxLength(20)
                    ->unique(Employee::class, 'registration_number', ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('department')
                    ->label('Lotação')
                    ->required()
                    ->maxLength(255),
                TextInput::make('position')
                    ->label('Cargo / Função')
                    ->required()
                    ->maxLength(255),
                Select::make('shift')
                    ->label('Turno')
                    ->options(Turno::class)
                    ->default(Turno::Morning)
                    ->required(),
                TextInput::make('payroll_code')
                    ->label('Rubrica')
                    ->maxLength(20),
                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }
}
