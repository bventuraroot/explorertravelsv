<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas del Cliente</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #007bff; padding-bottom: 12px; }
        .company-name { font-size: 22px; font-weight: bold; color: #007bff; margin-bottom: 4px; }
        .report-title { font-size: 16px; font-weight: bold; margin: 8px 0; }
        .report-info { font-size: 9px; color: #666; }
        .client-info { background-color: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin-bottom: 20px; font-size: 12px; }
        .client-info strong { color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: center; font-size: 9px; }
        th { background-color: #007bff; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .product-list { margin: 0; padding: 0 0 0 12px; }
        .product-list li { margin: 2px 0; }
    </style>
</head>
<body>
    @php
        $salesGrouped = collect($salesDetails)->groupBy('sale_id')->map(function ($rows) {
            $first = $rows->first();
            $products = $rows->map(function ($r) {
                return (object)[
                    'name' => $r->product_name,
                    'qty'  => $r->quantity,
                    'price'=> $r->pricesale,
                    'exempt'=> $r->exempt
                ];
            });
            return (object)[
                'sale_id'        => $first->sale_id,
                'date'           => $first->date,
                'formatted_date' => $first->formatted_date,
                'totalamount'    => $first->totalamount,
                'state'          => $first->state,
                'document_type'  => $first->document_type,
                'client_name'    => $first->client_name,
                'products'       => $products
            ];
        });
        
        $firstSale = $salesGrouped->first();
        $clientName = $firstSale ? $firstSale->client_name : 'Cliente';
        $totalAmount = $salesGrouped->where('state', 1)->sum('totalamount');
    @endphp

    <div class="header">
        <div class="company-name">{{ $heading->name ?? 'REPORTE DETALLADO DE CLIENTE' }}</div>
        <div class="report-title">Historial de Ventas</div>
        <div class="report-info">
            Fecha de Emisión: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="client-info">
        Cliente: <strong>{{ $clientName }}</strong>
    </div>

    @if($salesGrouped->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width:70px;">Fecha</th>
                    <th style="width:90px;">Tipo Doc.</th>
                    <th style="width:70px;">Estado</th>
                    <th>Productos Adquiridos</th>
                    <th style="width:90px;">Total Venta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesGrouped as $sale)
                    <tr>
                        <td>{{ $sale->formatted_date }}<br><small style="color:#666;">#{{ $sale->sale_id }}</small></td>
                        <td>{{ $sale->document_type }}</td>
                        <td>
                            <span style="color: {{ $sale->state == 1 ? '#155724' : '#721c24' }}; font-weight: bold;">
                                {{ $sale->state == 1 ? 'Completada' : 'Cancelada' }}
                            </span>
                        </td>
                        <td class="text-left">
                            <ul class="product-list">
                                @foreach($sale->products as $p)
                                    <li>
                                        {{ $p->name }}
                                        <small style="color:#555;">(×{{ number_format($p->qty, 0) }} · ${{ number_format($p->price, 2) }}c/u)</small>
                                        @if($p->exempt)
                                            <span style="background:#e0f2fe; color:#0369a1; padding: 1px 3px; font-size: 7px; border-radius: 3px;">Exento</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-right" style="font-weight: bold; color: {{ $sale->state == 1 ? '#155724' : '#721c24' }};">
                            ${{ number_format($sale->totalamount, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f3f5; font-weight: bold;">
                    <td colspan="4" class="text-right" style="font-size: 10px;">Total Acumulado (Completadas):</td>
                    <td class="text-right" style="font-size: 11px; color: #155724;">${{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div style="text-align:center; padding:40px; font-size: 12px; color: #777;">No se registraron ventas en el período.</div>
    @endif
</body>
</html>
