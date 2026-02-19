<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Folha de Frequência</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #000;
        }

        .page {
            padding: 10mm 12mm 8mm 12mm;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 3pt;
        }

        .header .brasao {
            font-size: 22pt;
            line-height: 1;
            margin-bottom: 2pt;
        }

        .header .estado {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header .orgao {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header .titulo {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4pt;
            letter-spacing: 1pt;
        }

        /* ── Info block ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
            margin-bottom: 0;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 2pt 3pt;
            font-size: 7pt;
            vertical-align: middle;
        }

        .info-table .lbl {
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
        }

        .info-table .val {
            font-size: 8pt;
            text-transform: uppercase;
            text-align: center;
        }

        /* ── Frequency table ── */
        .freq-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .freq-table th,
        .freq-table td {
            border: 1px solid #000;
            padding: 1.2pt 2pt;
            text-align: center;
            font-size: 7pt;
            vertical-align: middle;
        }

        .freq-table th {
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
            background-color: #f0f0f0;
        }

        .freq-table .col-dia {
            width: 22pt;
            font-weight: bold;
        }

        .freq-table .col-hora {
            width: 32pt;
        }

        .freq-table .col-rubrica {
            width: 38pt;
        }

        .freq-table td.dia-num {
            font-weight: bold;
        }

        .freq-table td.hora-val {
            font-size: 7.5pt;
        }

        /* ── Footer ── */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .footer-table td {
            border: 1px solid #000;
            padding: 2pt 3pt;
            font-size: 7pt;
            vertical-align: top;
        }

        .footer-table .lbl {
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
        }

        .sig-area {
            height: 22pt;
        }

        .sig-line-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .sig-line-table td {
            border: 1px solid #000;
            padding: 2pt 4pt;
            font-size: 6.5pt;
            height: 16pt;
            vertical-align: bottom;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Header ── --}}
    <div class="header">
        <div class="brasao">⚜</div>
        <div class="estado">Estado do Maranhão</div>
        <div class="orgao">Agência Estadual de Defesa Agropecuária do Maranhão – AGED-MA</div>
        <div class="titulo">Folha Individual de Frequência</div>
    </div>

    {{-- ── Employee info ── --}}
    @php
        $startFmt = $period->start_date->format('d/m/Y');
        $endFmt   = $period->end_date->format('d/m/Y');
    @endphp

    <table class="info-table">
        <tr>
            <td style="width:13%" class="lbl">Inscrição</td>
            <td style="width:44%" class="lbl">Nome</td>
            <td style="width:43%" class="lbl">Mês/Ano</td>
        </tr>
        <tr>
            <td class="val">{{ $employee->registration_number }}</td>
            <td class="val">{{ strtoupper($employee->name) }}</td>
            <td class="val">{{ $startFmt }} a {{ $endFmt }}</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 1pt 3pt;">
                <span class="lbl">Lotação: </span>
                <span style="text-transform:uppercase; font-size:7.5pt">{{ $employee->department }}</span>
            </td>
            <td>
                <span class="lbl">Cargo/Função: </span>
                <span style="text-transform:uppercase; font-size:7.5pt">{{ $employee->position }}</span>
            </td>
        </tr>
    </table>

    {{-- ── Frequency table ── --}}
    <table class="freq-table">
        {{-- Group headers --}}
        <tr>
            <th rowspan="3" class="col-dia">Dia</th>
            <th colspan="4">Manhã</th>
            <th colspan="4">Tarde</th>
        </tr>
        <tr>
            <th colspan="2">Entrada</th>
            <th colspan="2">Saída</th>
            <th colspan="2">Entrada</th>
            <th colspan="2">Saída</th>
        </tr>
        <tr>
            <th class="col-hora">Hora</th>
            <th class="col-rubrica">Rubrica</th>
            <th class="col-hora">Hora</th>
            <th class="col-rubrica">Rubrica</th>
            <th class="col-hora">Hora</th>
            <th class="col-rubrica">Rubrica</th>
            <th class="col-hora">Hora</th>
            <th class="col-rubrica">Rubrica</th>
        </tr>

        {{-- Day rows ── always 31 rows --}}
        @php
            $daysByNumber = collect($days)->keyBy('number');
        @endphp

        @for ($d = 1; $d <= 31; $d++)
            @php
                $day = $daysByNumber->get($d);

                if (! $day) {
                    // Day doesn't exist in this month — blank row
                    $me = $mx = $te = $tx = '';
                    $obs = '';
                    $isSpecial = false;
                } else {
                    $getTime = function (\App\Enums\TipoBatida $type) use ($day): string {
                        return isset($day['entries'][$type->value])
                            ? \Carbon\Carbon::parse($day['entries'][$type->value]->recorded_at)->format('H:i')
                            : '';
                    };

                    $isSpecial = $day['isWeekend'] || $day['holiday'] !== null || $day['note'] !== null;

                    if ($isSpecial) {
                        $me = $mx = $te = $tx = '';
                    } else {
                        $me = $getTime(\App\Enums\TipoBatida::MorningEntry);
                        $mx = $getTime(\App\Enums\TipoBatida::MorningExit);
                        $te = $getTime(\App\Enums\TipoBatida::AfternoonEntry);
                        $tx = $getTime(\App\Enums\TipoBatida::AfternoonExit);
                    }
                }
            @endphp

            <tr style="{{ ! $day ? 'color:#bbb' : '' }}">
                <td class="dia-num">{{ str_pad((string) $d, 2, '0', STR_PAD_LEFT) }}</td>
                <td class="hora-val">{{ $me }}</td>
                <td></td>
                <td class="hora-val">{{ $mx }}</td>
                <td></td>
                <td class="hora-val">{{ $te }}</td>
                <td></td>
                <td class="hora-val">{{ $tx }}</td>
                <td></td>
            </tr>
        @endfor
    </table>

    {{-- ── Footer ── --}}
    <table class="footer-table">
        <tr>
            <td colspan="2" style="height:28pt; vertical-align:top">
                <span class="lbl">Observação</span><br>
                @php
                    $obsLines = collect($days)->filter(fn($d) => $d['isWeekend'] === false && ($d['holiday'] !== null || $d['note'] !== null));
                @endphp
                @foreach($obsLines as $od)
                    @php
                        $label = str_pad((string) $od['number'], 2, '0', STR_PAD_LEFT) . ' — ';
                        if ($od['holiday'] !== null) {
                            $label .= 'Feriado: ' . $od['holiday']->description;
                        } elseif ($od['note'] !== null) {
                            $label .= $od['note']->type->getLabel();
                            if ($od['note']->notes) { $label .= ': ' . $od['note']->notes; }
                        }
                    @endphp
                    <span style="font-size:6.5pt">{{ $label }}</span><br>
                @endforeach
            </td>
        </tr>
    </table>

    <table class="sig-line-table">
        <tr>
            <td style="width:50%; height:30pt; vertical-align:bottom">
                <span class="lbl">Visto:</span>
            </td>
            <td style="width:50%; height:30pt; vertical-align:bottom; text-align:right">
                <span class="lbl">Visto</span>
            </td>
        </tr>
        <tr>
            <td style="height:18pt; vertical-align:bottom">
                <span style="font-size:6pt">Responsável pela frequência</span>
            </td>
            <td style="height:18pt; vertical-align:bottom; text-align:center">
                <span style="font-size:6pt">Assinatura do Chefe Imediato</span>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
