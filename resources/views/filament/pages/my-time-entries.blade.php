<x-filament-panels::page>
    <x-filament::section>
        {{-- Filters --}}
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label for="month-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Mês
                </label>
                <select
                    id="month-select"
                    wire:model.live="selectedMonth"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                >
                    @foreach ($this->getMonthOptions() as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="year-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Ano
                </label>
                <select
                    id="year-select"
                    wire:model.live="selectedYear"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                >
                    @foreach ($this->getYearOptions() as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex flex-wrap gap-4 text-xs">
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded bg-gray-200 dark:bg-gray-700"></span>
                Fim de semana
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded bg-success-200 dark:bg-success-900"></span>
                Feriado
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded bg-warning-200 dark:bg-warning-900"></span>
                Ponto Facultativo
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded bg-info-200 dark:bg-info-900"></span>
                Dispensa
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded bg-danger-200 dark:bg-danger-900"></span>
                Falta
            </span>
        </div>
    </x-filament::section>

    {{-- Calendar table --}}
    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        <th class="px-3 py-2">Dia</th>
                        <th class="px-3 py-2">Dia da Semana</th>
                        <th class="px-3 py-2">Entrada Manhã</th>
                        <th class="px-3 py-2">Saída Manhã</th>
                        <th class="px-3 py-2">Entrada Tarde</th>
                        <th class="px-3 py-2">Saída Tarde</th>
                        <th class="px-3 py-2">Observação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($this->getCalendarDays() as $day)
                        <tr class="
                            @switch($day['type'])
                                @case('weekend')
                                    bg-gray-50 text-gray-400 dark:bg-gray-950 dark:text-gray-500
                                    @break
                                @case('holiday')
                                    bg-success-50 dark:bg-success-950
                                    @break
                                @case('optional')
                                    bg-warning-50 dark:bg-warning-950
                                    @break
                                @case('dispensation')
                                    bg-info-50 dark:bg-info-950
                                    @break
                                @case('absent')
                                    bg-danger-50 dark:bg-danger-950
                                    @break
                                @default
                            @endswitch
                        ">
                            <td class="whitespace-nowrap px-3 py-2 font-medium">{{ $day['date'] }}</td>
                            <td class="whitespace-nowrap px-3 py-2">{{ $day['weekday'] }}</td>

                            @if (in_array($day['type'], ['weekend', 'holiday', 'optional', 'dispensation']))
                                <td class="px-3 py-2" colspan="4"></td>
                            @else
                                <td class="whitespace-nowrap px-3 py-2">{{ $day['morning_entry'] ?? '--:--' }}</td>
                                <td class="whitespace-nowrap px-3 py-2">{{ $day['morning_exit'] ?? '--:--' }}</td>
                                <td class="whitespace-nowrap px-3 py-2">{{ $day['afternoon_entry'] ?? '--:--' }}</td>
                                <td class="whitespace-nowrap px-3 py-2">{{ $day['afternoon_exit'] ?? '--:--' }}</td>
                            @endif

                            <td class="whitespace-nowrap px-3 py-2">
                                @if ($day['type'] === 'absent')
                                    <span class="inline-flex items-center gap-1 font-semibold text-danger-600 dark:text-danger-400">
                                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4" />
                                        {{ $day['observation'] }}
                                    </span>
                                @elseif ($day['observation'])
                                    <span class="text-gray-600 dark:text-gray-400">{{ $day['observation'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
