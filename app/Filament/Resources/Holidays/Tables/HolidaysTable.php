<?php

namespace App\Filament\Resources\Holidays\Tables;

use App\Enums\HolidayType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                IconColumn::make('recurrent')
                    ->label('Recorrente')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('scope')
                    ->label('Abrangência')
                    ->sortable(),

                TextColumn::make('employees_count')
                    ->label('Dispensados')
                    ->counts('employees')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(HolidayType::class),

                SelectFilter::make('month')
                    ->label('Mês')
                    ->options([
                        '1' => 'Janeiro',
                        '2' => 'Fevereiro',
                        '3' => 'Março',
                        '4' => 'Abril',
                        '5' => 'Maio',
                        '6' => 'Junho',
                        '7' => 'Julho',
                        '8' => 'Agosto',
                        '9' => 'Setembro',
                        '10' => 'Outubro',
                        '11' => 'Novembro',
                        '12' => 'Dezembro',
                    ])
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereMonth('date', $data['value'])
                        : $query
                    ),

                SelectFilter::make('year')
                    ->label('Ano')
                    ->options(fn () => collect(range(now()->year - 1, now()->year + 1))
                        ->mapWithKeys(fn (int $year) => [$year => (string) $year])
                        ->toArray()
                    )
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereYear('date', $data['value'])
                        : $query
                    ),
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
