<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\Shift;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('inscription')
                    ->label('Matrícula')
                    ->required()
                    ->maxLength(255)
                    ->unique(Employee::class, 'inscription', ignoreRecord: true),

                TextInput::make('department')
                    ->label('Lotação')
                    ->required()
                    ->maxLength(255),

                TextInput::make('position')
                    ->label('Cargo/Função')
                    ->required()
                    ->maxLength(255),

                TextInput::make('organization')
                    ->label('Organização')
                    ->required()
                    ->maxLength(255),

                Select::make('default_shift')
                    ->label('Turno Padrão')
                    ->options(Shift::class)
                    ->required(),

                Fieldset::make('Acesso do Colaborador')
                    ->visible(fn (string $context): bool => $context === 'create')
                    ->components([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email'),

                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
