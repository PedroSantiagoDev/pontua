<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Marcar Ponto
        </x-slot>

        <x-slot name="description">
            {{ now()->translatedFormat('l, d \d\e F \d\e Y') }}
        </x-slot>

        <div class="space-y-6">
            {{-- Shift selector --}}
            <div>
                <label for="shift-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Turno
                </label>
                <select
                    id="shift-select"
                    wire:model.live="selectedShift"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                >
                    <option value="morning">Manhã (08:00 – 14:00)</option>
                    <option value="afternoon">Tarde (13:00 – 19:00)</option>
                </select>

                @if ($selectedShift !== $defaultShiftValue)
                    @php
                        $defaultShiftLabel = \App\Enums\Shift::from($defaultShiftValue)->getLabel();
                    @endphp
                    <p class="mt-1 text-xs text-warning-600 dark:text-warning-400">
                        Turno diferente do padrão ({{ $defaultShiftLabel }})
                    </p>
                @endif
            </div>

            {{-- Today's entries --}}
            @php
                $entries = $this->getTodayEntries();
                $nextField = $this->getNextField();
            @endphp

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ([
                    'morning_entry' => 'Entrada Manhã',
                    'morning_exit' => 'Saída Manhã',
                    'afternoon_entry' => 'Entrada Tarde',
                    'afternoon_exit' => 'Saída Tarde',
                ] as $field => $label)
                    <div class="rounded-lg border p-3 text-center
                        {{ $entries[$field] ? 'border-success-300 bg-success-50 dark:border-success-600 dark:bg-success-950' : 'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800' }}
                    ">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-1 text-lg font-semibold {{ $entries[$field] ? 'text-success-600 dark:text-success-400' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $entries[$field] ?? '--:--' }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Clock in button --}}
            <div class="flex items-center justify-center">
                @if ($nextField)
                    <x-filament::button
                        wire:click="clockIn"
                        size="xl"
                        icon="heroicon-o-clock"
                    >
                        {{ $nextField['label'] }}
                    </x-filament::button>
                @else
                    <div class="rounded-lg bg-success-50 px-6 py-3 text-center dark:bg-success-950">
                        <p class="font-medium text-success-600 dark:text-success-400">
                            Todos os pontos do turno foram registrados hoje.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
