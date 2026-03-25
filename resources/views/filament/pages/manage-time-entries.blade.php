<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <x-filament::input.wrapper class="w-64">
                <x-filament::input.select wire:model.live="selectedEmployeeId">
                    <option value="">— Selecione um colaborador —</option>
                    @foreach ($this->getEmployees() as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="w-48">
                <x-filament::input.select wire:model.live="selectedMonth">
                    @foreach ($this->getMonthOptions() as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="w-28">
                <x-filament::input.select wire:model.live="selectedYear">
                    @foreach ($this->getYearOptions() as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex flex-wrap gap-2">
            <x-filament::badge color="gray">Fim de semana</x-filament::badge>
            <x-filament::badge color="success">Feriado</x-filament::badge>
            <x-filament::badge color="warning">Ponto Facultativo</x-filament::badge>
            <x-filament::badge color="info">Dispensa</x-filament::badge>
            <x-filament::badge color="danger">Falta</x-filament::badge>
        </div>
    </x-filament::section>

    @if ($selectedEmployeeId)
        {{ $this->table }}
    @else
        <x-filament::section>
            <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Selecione um colaborador para visualizar e editar os pontos.
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
