<?php

namespace App\Filament\Resources\Periods\Schemas;

use App\Enums\StatusPeriodo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class PeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('month')
                    ->label('Mês')
                    ->options([
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro',
                    ])
                    ->required(),
                Select::make('year')
                    ->label('Ano')
                    ->options(function (): array {
                        $currentYear = (int) now()->year;
                        $years = [];
                        for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
                            $years[$y] = (string) $y;
                        }

                        return $years;
                    })
                    ->default(now()->year)
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Data de Início')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Data de Fim')
                    ->required()
                    ->afterOrEqual('start_date'),
                Select::make('status')
                    ->label('Status')
                    ->options(StatusPeriodo::class)
                    ->default(StatusPeriodo::Open)
                    ->required(),
            ]);
    }
}
