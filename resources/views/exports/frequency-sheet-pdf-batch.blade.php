<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h2, .header h3 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
        }

        .header h3 {
            margin-top: 2px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            text-align: center;
            background-color: #f0f0f0;
        }

        .info-value {
            text-align: center;
        }

        .freq-table {
            margin-top: 5px;
        }

        .freq-table th, .freq-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: center;
            font-size: 9px;
            vertical-align: middle;
        }

        .freq-table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .freq-table .day-col {
            width: 30px;
        }

        .falta {
            color: #000;
            font-weight: bold;
        }

        .rubrica {
            font-size: 7px;
        }

        .obs-section {
            margin-top: 5px;
        }

        .obs-section .obs-label {
            font-weight: bold;
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
            font-size: 10px;
        }

        .obs-section .obs-content {
            border: 1px solid #000;
            border-top: none;
            padding: 5px;
            min-height: 20px;
            font-size: 9px;
        }

        .signatures {
            margin-top: 30px;
            width: 100%;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
            font-size: 10px;
            vertical-align: bottom;
        }

        .sig-line {
            border-top: 1px solid #000;
            display: inline-block;
            width: 200px;
            padding-top: 3px;
        }

        .visto-label {
            font-weight: bold;
            text-align: left;
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    @foreach ($sheets as $sheet)
        <div @if (! $loop->last) class="page-break" @endif>
            <div class="header">
                <h2>ESTADO DO MARANHÃO</h2>
                <h3>AGÊNCIA ESTADUAL DE DEFESA AGROPECUÁRIA DO MARANHÃO – AGED-MA</h3>
            </div>

            <div class="title">FOLHA INDIVIDUAL DE FREQÜÊNCIA</div>

            <table class="info-table">
                <tr>
                    <td class="info-label" colspan="2">INSCRIÇÃO</td>
                    <td class="info-label" colspan="4">NOME</td>
                    <td class="info-label" colspan="3">MÊS/ANO</td>
                </tr>
                <tr>
                    <td class="info-value" colspan="2" rowspan="2">{{ $sheet['employee']->inscription }}</td>
                    <td class="info-value" colspan="4" rowspan="2">{{ $sheet['employee']->name }}</td>
                    <td class="info-value" colspan="3">{{ $sheet['period'] }}</td>
                </tr>
                <tr>
                    <td class="info-label" colspan="3">CARGO/FUNÇÃO</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">LOTAÇÃO:</td>
                    <td></td>
                    <td class="info-value">{{ $sheet['employee']->department }}</td>
                    <td colspan="3"></td>
                    <td class="info-value" colspan="3" rowspan="2">{{ $sheet['employee']->position }}</td>
                </tr>
            </table>

            <table class="freq-table">
                <thead>
                    <tr>
                        <th class="day-col" rowspan="3">DIA</th>
                        <th colspan="4">MANHÃ</th>
                        <th colspan="4">TARDE</th>
                    </tr>
                    <tr>
                        <th colspan="2">ENTRADA</th>
                        <th colspan="2">SAIDA</th>
                        <th colspan="2">ENTRADA</th>
                        <th colspan="2">SAIDA</th>
                    </tr>
                    <tr>
                        <th>HORA</th>
                        <th>RUBRICA</th>
                        <th>HORA</th>
                        <th>RUBRICA</th>
                        <th>HORA</th>
                        <th>RUBRICA</th>
                        <th>HORA</th>
                        <th>RUBRICA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheet['days'] as $day)
                        <tr>
                            <td style="font-weight: bold;">{{ $day['number'] }}</td>
                            @if ($day['type'] === 'absent')
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                                <td class="falta">FALTA</td>
                            @elseif ($day['type'] === 'present')
                                <td>{{ $day['morning_entry'] ?? '' }}</td>
                                <td class="rubrica">{{ $day['morning_entry'] ? $day['rubrica'] : '' }}</td>
                                <td>{{ $day['morning_exit'] ?? '' }}</td>
                                <td class="rubrica">{{ $day['morning_exit'] ? $day['rubrica'] : '' }}</td>
                                <td>{{ $day['afternoon_entry'] ?? '' }}</td>
                                <td class="rubrica">{{ $day['afternoon_entry'] ? $day['rubrica'] : '' }}</td>
                                <td>{{ $day['afternoon_exit'] ?? '' }}</td>
                                <td class="rubrica">{{ $day['afternoon_exit'] ? $day['rubrica'] : '' }}</td>
                            @else
                                <td></td><td></td><td></td><td></td>
                                <td></td><td></td><td></td><td></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="obs-section">
                <div class="obs-label">OBSERVAÇÃO</div>
                <div class="obs-content">
                    @if (count($sheet['observations']) > 0)
                        {{ implode('; ', $sheet['observations']) }}
                    @endif
                </div>
            </div>

            <table class="signatures">
                <tr>
                    <td class="visto-label">VISTO:</td>
                    <td class="visto-label">VISTO</td>
                </tr>
                <tr>
                    <td>
                        <div class="sig-line">Responsável pela freqüência</div>
                    </td>
                    <td>
                        <div class="sig-line">Assinatura do Chefe Imediato</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
