<?php

namespace App\Filament\Resources\Holidays\Tables;

use App\Enums\TipoFeriado;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HolidaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (TipoFeriado $state): string => match ($state) {
                        TipoFeriado::National => 'danger',
                        TipoFeriado::State => 'warning',
                        TipoFeriado::Municipal => 'info',
                    }),
            ])
            ->defaultSort('date')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(TipoFeriado::class),
                Filter::make('year')
                    ->label('Ano Atual')
                    ->query(fn (Builder $query): Builder => $query->whereYear('date', now()->year))
                    ->default(),
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
