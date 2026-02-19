<?php

namespace App\Filament\Resources\Periods\Tables;

use App\Enums\StatusPeriodo;
use App\Models\Period;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodsTable
{
    private static array $monthNames = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month')
                    ->label('Mês')
                    ->state(fn (Period $record): string => self::$monthNames[$record->month] ?? '')
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Ano')
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StatusPeriodo $state): string => match ($state) {
                        StatusPeriodo::Open => 'success',
                        StatusPeriodo::Closed => 'gray',
                    }),
            ])
            ->defaultSort('year', 'desc')
            ->filters([])
            ->recordActions([
                Action::make('close')
                    ->label('Fechar Período')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Fechar Período')
                    ->modalDescription('Tem certeza que deseja fechar este período? Esta ação não poderá ser desfeita facilmente.')
                    ->modalSubmitActionLabel('Fechar')
                    ->visible(fn (Period $record): bool => $record->status === StatusPeriodo::Open)
                    ->action(function (Period $record): void {
                        $record->update(['status' => StatusPeriodo::Closed]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
