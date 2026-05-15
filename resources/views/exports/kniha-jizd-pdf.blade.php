<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Kniha jízd — {{ $vehicle?->name ?? 'všechna vozidla' }} — {{ $from->format('d.m.Y') }}–{{ $to->format('d.m.Y') }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 16mm 12mm; size: A4 landscape; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #222; }
        h1 { font-size: 14pt; margin: 0 0 4mm 0; }
        h2 { font-size: 10pt; margin: 0 0 2mm 0; color: #555; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 4mm; margin-bottom: 4mm; }
        .header-row { display: table; width: 100%; }
        .header-cell { display: table-cell; vertical-align: top; width: 50%; }
        .meta { font-size: 8pt; color: #555; line-height: 1.5; }
        table.trips { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        table.trips th, table.trips td { border: 1px solid #ccc; padding: 1.5mm 2mm; text-align: left; vertical-align: top; }
        table.trips th { background: #f3f4f6; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5px; }
        table.trips td.r { text-align: right; }
        table.trips tr:nth-child(even) td { background: #fafafa; }
        .totals { margin-top: 4mm; }
        .totals table { width: auto; border-collapse: collapse; }
        .totals td { padding: 1.5mm 4mm 1.5mm 0; font-size: 9pt; }
        .totals td.label { color: #555; }
        .totals td.value { font-weight: 700; font-size: 11pt; }
        .signature { margin-top: 12mm; display: table; width: 100%; }
        .signature-cell { display: table-cell; width: 50%; padding: 0 4mm; }
        .signature-line { border-top: 1px solid #999; padding-top: 1mm; margin-top: 14mm; font-size: 8pt; color: #555; }
        .footer { margin-top: 6mm; font-size: 7pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-row">
            <div class="header-cell">
                <h1>Kniha jízd</h1>
                <div class="meta">
                    Období: <b>{{ $from->locale('cs_CZ')->isoFormat('D. M. YYYY') }} – {{ $to->locale('cs_CZ')->isoFormat('D. M. YYYY') }}</b><br>
                    @if ($vehicle)
                        Vozidlo: <b>{{ $vehicle->name }}</b>, SPZ <b>{{ $vehicle->plate }}</b>@if($vehicle->color), {{ $vehicle->color }}@endif<br>
                        @if ($vehicleVin) VIN: {{ $vehicleVin }}<br>@endif
                    @else
                        Vozidla: <b>všechna</b><br>
                    @endif
                </div>
            </div>
            <div class="header-cell" style="text-align: right;">
                <h2>{{ $company['name'] }}</h2>
                <div class="meta">
                    @if($company['address']) {{ $company['address'] }}<br>@endif
                    @if($company['ico']) IČO: {{ $company['ico'] }}@endif @if($company['dic']) · DIČ: {{ $company['dic'] }}@endif<br>
                    Vytvořeno: {{ now()->locale('cs_CZ')->isoFormat('D. M. YYYY HH:mm') }}
                </div>
            </div>
        </div>
    </div>

    @if ($trips->isEmpty())
        <p style="text-align:center; color:#888; margin-top: 20mm;">V zadaném období nebyly zaznamenány žádné jízdy.</p>
    @else
        <table class="trips">
            <thead>
                <tr>
                    <th style="width: 6%;">Datum</th>
                    <th style="width: 5%;">Čas zah.</th>
                    <th style="width: 20%;">Místo zahájení</th>
                    <th style="width: 5%;">Čas konce</th>
                    <th style="width: 20%;">Místo konce</th>
                    <th style="width: 5%;">Doba</th>
                    <th style="width: 5%;">Km</th>
                    @if (!$vehicle)
                        <th style="width: 9%;">Vozidlo</th>
                    @endif
                    <th style="width: 10%;">Řidič</th>
                    <th>Poznámka</th>
                </tr>
            </thead>
            <tbody>
                @php $totalKm = 0; $totalSecs = 0; @endphp
                @foreach ($trips as $t)
                    @php
                        $secs = $t->duration_seconds ?: ($t->ended_at && $t->started_at ? $t->ended_at->timestamp - $t->started_at->timestamp : 0);
                        $km = $t->distance_meters ? $t->distance_meters / 1000 : 0;
                        $totalKm += $km; $totalSecs += $secs;
                    @endphp
                    <tr>
                        <td>{{ $t->started_at?->format('d.m.Y') }}</td>
                        <td>{{ $t->started_at?->format('H:i') }}</td>
                        <td>{{ $t->start_address ?? '—' }}</td>
                        <td>{{ $t->ended_at?->format('H:i') ?? '—' }}</td>
                        <td>{{ $t->end_address ?? '— probíhá —' }}</td>
                        <td class="r">{{ $secs ? sprintf('%d:%02d', intdiv($secs, 3600), intdiv($secs % 3600, 60)) : '—' }}</td>
                        <td class="r">{{ $km ? number_format($km, 1, ',', ' ') : '—' }}</td>
                        @if (!$vehicle)
                            <td>{{ $t->vehicle?->plate ?? '—' }}</td>
                        @endif
                        <td>{{ $t->driver?->full_name ?? '—' }}</td>
                        <td>{{ $t->note ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td class="label">Počet jízd:</td>
                    <td class="value">{{ $trips->count() }}</td>
                </tr>
                <tr>
                    <td class="label">Najetých km celkem:</td>
                    <td class="value">{{ number_format($totalKm, 1, ',', ' ') }} km</td>
                </tr>
                <tr>
                    <td class="label">Celková doba jízd:</td>
                    <td class="value">{{ intdiv($totalSecs, 3600) }} h {{ str_pad((string) intdiv($totalSecs % 3600, 60), 2, '0', STR_PAD_LEFT) }} min</td>
                </tr>
            </table>
        </div>

        <div class="signature">
            <div class="signature-cell">
                <div class="signature-line">Datum, podpis odpovědné osoby</div>
            </div>
            <div class="signature-cell">
                <div class="signature-line">Razítko</div>
            </div>
        </div>
    @endif

    <div class="footer">
        gpsmanager — Kniha jízd dle § 24 odst. 2 písm. k) zákona č. 586/1992 Sb. o daních z příjmů. Stránka <script type="text/php">if (isset($pdf)) { $pdf->page_text(285, 568, "{PAGE_NUM} / {PAGE_COUNT}", null, 7, [0.5,0.5,0.5]); }</script>
    </div>
</body>
</html>
