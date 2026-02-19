<x-filament-panels::page>
    {{-- Employee + period info --}}
    <x-filament::section>
        <div class="flex flex-wrap items-start gap-8">
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-identification" class="h-10 w-10 text-primary-500" />
                <div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $employee->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Matrícula: {{ $employee->registration_number }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-6 text-sm text-gray-600 dark:text-gray-400">
                <div><span class="font-medium">Lotação:</span> {{ $employee->department }}</div>
                <div><span class="font-medium">Cargo:</span> {{ $employee->position }}</div>
                <div>
                    <span class="font-medium">Turno:</span>
                    <x-filament::badge :color="$employee->shift->value === 'morning' ? 'info' : 'warning'">
                        {{ $employee->shift->getLabel() }}
                    </x-filament::badge>
                </div>
                <div>
                    <span class="font-medium">Período:</span> {{ $monthName }} / {{ $period->year }}
                    <x-filament::badge :color="$period->status->value === 'open' ? 'success' : 'gray'" class="ml-1">
                        {{ $period->status->getLabel() }}
                    </x-filament::badge>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Day grid --}}
    <x-filament::section :compact="true">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($days as $day)
                @php
                    $rowClass = match(true) {
                        $day['isWeekend']        => 'bg-gray-50 dark:bg-gray-900',
                        $day['holiday'] !== null => 'bg-gray-50 dark:bg-gray-900',
                        $day['note'] !== null    => 'bg-warning-50 dark:bg-warning-950',
                        $day['missingPunch']     => 'bg-danger-50 dark:bg-danger-950',
                        default                  => '',
                    };
                @endphp

                <div class="flex flex-wrap items-center gap-3 rounded-lg px-3 py-2 {{ $rowClass }}">

                    {{-- Day number + weekday --}}
                    <div class="flex w-16 shrink-0 items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-gray-700 dark:text-gray-300">
                            {{ $day['number'] }}
                        </span>
                        <span class="text-xs font-medium text-gray-400">{{ $day['weekdayShort'] }}</span>
                    </div>

                    {{-- Day label --}}
                    <div class="w-44 shrink-0 text-sm">
                        @if($day['isWeekend'])
                            <span class="text-gray-400">Fim de semana</span>
                        @elseif($day['holiday'] !== null)
                            <x-filament::badge color="gray">{{ $day['holiday']->description }}</x-filament::badge>
                        @elseif($day['note'] !== null)
                            <x-filament::badge color="warning">{{ $day['note']->type->getLabel() }}</x-filament::badge>
                        @elseif($day['missingPunch'])
                            <x-filament::badge color="danger">Sem registro</x-filament::badge>
                        @endif
                    </div>

                    {{-- Recorded punches (read-only) --}}
                    <div class="flex flex-wrap items-center gap-4">
                        @foreach($day['punchTypes'] as $punchType)
                            @php $recorded = $day['entries'][$punchType->value] ?? null; @endphp
                            @if(! $day['isWeekend'] && $day['holiday'] === null && $day['note'] === null)
                                <div class="flex flex-col items-center">
                                    <span class="text-xs text-gray-400">{{ $punchType->getLabel() }}</span>
                                    @if($recorded)
                                        <span class="font-mono text-sm font-bold text-success-600">
                                            {{ \Carbon\Carbon::parse($recorded->recorded_at)->format('H:i') }}
                                        </span>
                                    @else
                                        <span class="font-mono text-sm text-{{ $day['isFuture'] ? 'gray-300 dark:text-gray-600' : 'danger-400' }}">
                                            --:--
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        {{-- Note details if free text --}}
                        @if($day['note'] !== null && $day['note']->notes)
                            <span class="text-xs text-gray-500 italic">{{ $day['note']->notes }}</span>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
