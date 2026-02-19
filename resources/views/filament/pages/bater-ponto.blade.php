<x-filament-panels::page>
    @php
        $today = collect($days)->firstWhere('isToday', true);
        $shiftColor = $employee->shift->value === 'morning' ? 'info' : 'warning';

        $punchTypes = [
            \App\Enums\TipoBatida::MorningEntry,
            \App\Enums\TipoBatida::MorningExit,
            \App\Enums\TipoBatida::AfternoonEntry,
            \App\Enums\TipoBatida::AfternoonExit,
        ];

        $formatTime = fn ($entry) => $entry
            ? \Carbon\Carbon::parse($entry->recorded_at)->format('H:i')
            : '--:--';
    @endphp

    {{-- ── Top row: employee card + today punch card ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Employee profile card --}}
        <div class="flex flex-col justify-center gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900">
                    <x-filament::icon icon="heroicon-o-user" class="h-7 w-7 text-primary-600 dark:text-primary-400" />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-gray-900 dark:text-white">{{ $employee->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mat. {{ $employee->registration_number }}</p>
                </div>
            </div>
            <div class="space-y-1.5 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
                <div class="flex items-start gap-2">
                    <span class="w-16 shrink-0 text-gray-400">Lotação</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $employee->department }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="w-16 shrink-0 text-gray-400">Cargo</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $employee->position }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-16 shrink-0 text-gray-400">Turno</span>
                    <x-filament::badge :color="$shiftColor">{{ $employee->shift->getLabel() }}</x-filament::badge>
                </div>
            </div>
        </div>

        {{-- Today punch card --}}
        <div class="lg:col-span-2">
            @if(! $period)
                <div class="flex h-full items-center justify-center gap-3 rounded-xl border border-warning-200 bg-warning-50 p-8 text-warning-700 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6 shrink-0" />
                    <p class="font-medium">Não há período aberto. Aguarde a abertura pelo administrador.</p>
                </div>
            @elseif(! $today)
                <div class="flex h-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-8 text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-6 w-6 shrink-0" />
                    <p class="font-medium">O dia de hoje não está no período atual.</p>
                </div>
            @elseif($today['isWeekend'])
                <div class="flex h-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-8 text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                    <x-filament::icon icon="heroicon-o-face-smile" class="h-6 w-6 shrink-0" />
                    <p class="font-medium">Fim de semana — bom descanso!</p>
                </div>
            @elseif($today['holiday'] !== null)
                <div class="flex h-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-8 text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                    <x-filament::icon icon="heroicon-o-star" class="h-6 w-6 shrink-0" />
                    <p class="font-medium">Feriado: {{ $today['holiday']->description }}</p>
                </div>
            @elseif($today['note'] !== null)
                <div class="flex h-full items-center justify-center gap-3 rounded-xl border border-warning-200 bg-warning-50 p-8 text-warning-700 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-6 w-6 shrink-0" />
                    <p class="font-medium">{{ $today['note']->type->getLabel() }}{{ $today['note']->notes ? ' — '.$today['note']->notes : '' }}</p>
                </div>
            @else
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-widest text-gray-400">Hoje</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $today['date']->translatedFormat('l, d \d\e F') }}
                            </p>
                        </div>
                        <div wire:loading class="flex items-center gap-2 text-sm text-primary-500">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Registrando...
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach($today['punchTypes'] as $punchType)
                            @php $recorded = $today['entries'][$punchType->value] ?? null; @endphp
                            <div @class([
                                'flex flex-col items-center gap-2 rounded-xl p-4',
                                'bg-success-50 dark:bg-success-950' => $recorded,
                                'bg-gray-50 dark:bg-gray-800' => ! $recorded,
                            ])>
                                <span class="text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $punchType->getLabel() }}
                                </span>
                                @if($recorded)
                                    <x-filament::icon icon="heroicon-s-check-circle" class="h-6 w-6 text-success-500" />
                                    <span class="font-mono text-lg font-bold text-success-600 dark:text-success-400">
                                        {{ $formatTime($recorded) }}
                                    </span>
                                @else
                                    <x-filament::icon icon="heroicon-o-clock" class="h-6 w-6 text-gray-400" />
                                    <x-filament::button
                                        size="sm"
                                        wire:click="punch('{{ $punchType->value }}')"
                                        wire:loading.attr="disabled"
                                        color="primary"
                                    >
                                        Registrar
                                    </x-filament::button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Month history ── --}}
    @if($period)
        @php
            $workedCount = collect($days)
                ->filter(fn($d) => $d['isPast'] && ! $d['isWeekend'] && ! $d['holiday'] && ! $d['note'] && collect($d['entries'])->isNotEmpty())
                ->count();
        @endphp

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-gray-400" />
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ \Carbon\Carbon::createFromDate($period->year, $period->month, 1)->translatedFormat('F \d\e Y') }}
                    </h2>
                </div>
                <span class="text-xs text-gray-400">{{ $workedCount }} dias registrados</span>
            </div>

            {{-- Column headers --}}
            <div class="grid grid-cols-[56px_48px_1fr_1fr_1fr_1fr_1fr] border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:border-gray-800 dark:bg-gray-800/50">
                <span class="text-center">Dia</span>
                <span></span>
                <span class="text-center">Ent. Manhã</span>
                <span class="text-center">Saí. Manhã</span>
                <span class="text-center">Ent. Tarde</span>
                <span class="text-center">Saí. Tarde</span>
                <span class="text-center">Situação</span>
            </div>

            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($days as $day)
                    @php
                        $isSpecial = $day['isWeekend'] || $day['holiday'] !== null || $day['note'] !== null;
                    @endphp
                    <div @class([
                        'grid grid-cols-[56px_48px_1fr_1fr_1fr_1fr_1fr] items-center px-4 py-2 text-sm',
                        'bg-primary-50/60 dark:bg-primary-950/40' => $day['isToday'],
                        'bg-gray-50/70 dark:bg-gray-800/30 text-gray-400' => ! $day['isToday'] && ($day['isWeekend'] || $day['holiday'] !== null),
                        'bg-warning-50/60 dark:bg-warning-950/40' => $day['note'] !== null,
                        'bg-danger-50/40 dark:bg-danger-950/20' => $day['missingPunch'] && ! $isSpecial,
                    ])>
                        <div class="flex justify-center">
                            <span @class([
                                'flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold',
                                'bg-primary-500 text-white' => $day['isToday'],
                                'text-gray-700 dark:text-gray-300' => ! $day['isToday'],
                            ])>{{ $day['number'] }}</span>
                        </div>

                        <div class="text-center text-xs text-gray-400">{{ $day['weekdayShort'] }}</div>

                        @if($isSpecial)
                            <div class="col-span-5 text-xs">
                                @if($day['isWeekend'])
                                    <span class="text-gray-400">Fim de semana</span>
                                @elseif($day['holiday'] !== null)
                                    <x-filament::badge color="gray" size="sm">{{ $day['holiday']->description }}</x-filament::badge>
                                @elseif($day['note'] !== null)
                                    <x-filament::badge color="warning" size="sm">
                                        {{ $day['note']->type->getLabel() }}{{ $day['note']->notes ? ' — '.$day['note']->notes : '' }}
                                    </x-filament::badge>
                                @endif
                            </div>
                        @else
                            @foreach($punchTypes as $type)
                                @php $time = $formatTime($day['entries'][$type->value] ?? null); @endphp
                                <div class="text-center">
                                    @if($day['isFuture'])
                                        <span class="font-mono text-gray-200 dark:text-gray-700">--:--</span>
                                    @elseif($time !== '--:--')
                                        <span class="font-mono font-semibold text-success-600 dark:text-success-400">{{ $time }}</span>
                                    @else
                                        <span class="font-mono text-danger-400">--:--</span>
                                    @endif
                                </div>
                            @endforeach

                            <div class="text-center">
                                @if($day['isToday'])
                                    <x-filament::badge color="primary" size="sm">Hoje</x-filament::badge>
                                @elseif($day['missingPunch'] && $day['isPast'])
                                    <x-filament::badge color="danger" size="sm">Incompleto</x-filament::badge>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
