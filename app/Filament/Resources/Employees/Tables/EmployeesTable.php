<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Enums\Turno;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_number')
                    ->label('Matrícula')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->label('Lotação')
                    ->searchable(),
                TextColumn::make('position')
                    ->label('Cargo / Função')
                    ->searchable(),
                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->color(fn (Turno $state): string => match ($state) {
                        Turno::Morning => 'info',
                        Turno::Afternoon => 'warning',
                    }),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('shift')
                    ->label('Turno')
                    ->options(Turno::class),
                TernaryFilter::make('active')
                    ->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
