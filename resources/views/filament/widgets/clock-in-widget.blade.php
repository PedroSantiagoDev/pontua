<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Marcar Ponto
        </x-slot>

        <x-slot name="description">
            {{ now()->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y') }}
        </x-slot>

        <div class="space-y-4">
            {{-- Shift selector --}}
            <div class="space-y-2">
                <x-filament::input.wrapper class="w-full">
                    <x-filament::input.select wire:model.live="selectedShift">
                        <option value="morning">Manhã (08:00 – 12:00)</option>
                        <option value="afternoon">Tarde (13:00 – 19:00)</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                @if ($selectedShift !== $defaultShiftValue)
                    @php
                        $defaultShiftLabel = \App\Enums\Shift::from($defaultShiftValue)->getLabel();
                    @endphp
                    <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle">
                        Turno diferente do padrão ({{ $defaultShiftLabel }})
                    </x-filament::badge>
                @endif
            </div>

            {{-- Today's entries --}}
            @php
                $entries = $this->getTodayEntries();
                $nextField = $this->getNextField();
            @endphp

            <div class="space-y-2">
                @foreach ([
                    'Manhã' => ['morning_entry' => 'Entrada', 'morning_exit' => 'Saída'],
                    'Tarde' => ['afternoon_entry' => 'Entrada', 'afternoon_exit' => 'Saída'],
                ] as $period => $fields)
                    <div>
                        <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $period }}</p>
                        <div class="flex gap-2">
                            @foreach ($fields as $field => $label)
                                <div @class([
                                    'flex-1 rounded-lg border p-2.5 text-center',
                                    'border-success-300 bg-success-50 dark:border-success-600 dark:bg-success-950' => $entries[$field],
                                    'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800' => ! $entries[$field],
                                ])>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                    <div class="mt-1.5 flex justify-center">
                                        <x-filament::badge :color="$entries[$field] ? 'success' : 'gray'">
                                            {{ $entries[$field] ?? '--:--' }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Clock in button --}}
            <div class="flex items-center justify-center pt-1">
                @if ($nextField)
                    <x-filament::button
                        wire:click="clockIn"
                        size="xl"
                        icon="heroicon-o-clock"
                    >
                        {{ $nextField['label'] }}
                    </x-filament::button>
                @else
                    <x-filament::badge color="success" icon="heroicon-m-check-circle">
                        Todos os pontos do turno foram registrados hoje.
                    </x-filament::badge>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
