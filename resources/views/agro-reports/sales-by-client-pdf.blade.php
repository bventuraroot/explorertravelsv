<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas por Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 15px; }
        .company-name { font-size: 24px; font-weight: bold; color: #007bff; margin-bottom: 5px; }
        .report-title { font-size: 18px; font-weight: bold; margin: 10px 0; }
        .report-info { font-size: 10px; color: #666; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 9px; }
        th { background-color: #007bff; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $heading->name ?? 'REPORTE DE VENTAS POR CLIENTES' }}</div>
        <div class="report-title">Resumen por Cliente</div>
        <div class="report-info">
            Fecha: {{ now()->format('d/m/Y H:i:s') }} |
            @php
                $meses = [ '01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril','05' => 'Mayo','06' => 'Junio','07' => 'Julio','08' => 'Agosto','09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre' ];
            @endphp
            @if(isset($dateRange) && $dateRange)
                Período: {{ \Carbon\Carbon::parse(explode(' to ', $dateRange)[0])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(explode(' to ', $dateRange)[1])->format('d/m/Y') }}
            @else
                Período: {{ $yearB ?: 'Todos los años' }} - {{ $period ? ($meses[str_pad($period, 2, '0', STR_PAD_LEFT)] ?? $period) : 'Todos los meses' }}
            @endif
        </div>
    </div>

    @if($salesByClient && $salesByClient->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>NIT</th>
                    <th>Total Ventas</th>
                    <th>Monto Total</th>
                    <th>Primera Venta</th>
                    <th>Última Venta</th>
                </tr>
            </thead>
            <tbody>
                @php $i=1; @endphp
                @foreach($salesByClient as $client)
                    @php
                        if ($client->tpersona === 'J') {
                            $name = $client->name_contribuyente ?: $client->comercial_name ?: '—';
                        } else {
                            $name = implode(' ', array_filter([
                                $client->firstname, $client->secondname, $client->firstlastname, $client->secondlastname
                            ], fn($p) => !empty(trim($p ?? '')))) ?: '—';
                        }
                    @endphp
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td class="text-left">{{ $name }}</td>
                        <td>{{ $client->tpersona === 'J' ? 'Jurídica' : 'Natural' }}</td>
                        <td>{{ $client->nit ?: 'N/A' }}</td>
                        <td>{{ number_format($client->total_sales) }}</td>
                        <td class="text-right">${{ number_format($client->total_amount, 2) }}</td>
                        <td>{{ $client->first_sale_date ? \Carbon\Carbon::parse($client->first_sale_date)->format('d/m/Y') : '—' }}</td>
                        <td>{{ $client->last_sale_date ? \Carbon\Carbon::parse($client->last_sale_date)->format('d/m/Y') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align:center; padding:40px;">No se encontraron resultados</div>
    @endif
</body>
</html>
