<html>

<head>
<style>
    @page { margin: 80px 25px; }
    header { position: fixed; top: -60px; left: 0; right: 0; height: 60px; text-align: center; color: #000; }
    footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 30px; text-align: right; color: #000; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { border: 1px solid #000; padding: 4px; }
    .no-border { border: 0; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .nowrap { white-space: nowrap; }
    .small { font-size: 10px; }
</style>
</head>
<body>
<header>
    <div style="font-weight: bold;">LIBRO DE VENTAS CONSUMIDOR</div>
    <div class="small">
        <strong>Nombre del Contribuyente:</strong> {{ $heading['name'] }}
        &nbsp;&nbsp;<strong>N.R.C.:</strong> {{ $heading['ncr'] }}
        &nbsp;&nbsp;<strong>MES:</strong> {{ strtoupper($mesNombre) }}
        &nbsp;&nbsp;<strong>Año:</strong> {{ $yearB }}
        <div>(Valores expresados en Dólares Estadounidenses)</div>
    </div>
</header>

<footer>
    <script type="text/php">
        if (isset($pdf)) {
            $x = 520; $y = 800; $text = "Página {PAGE_NUM} de {PAGE_COUNT}"; $font = null; $size = 9; $color = array(0,0,0);
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
    &nbsp;
    </footer>

<main>
    <table>
        <thead>
            <tr>
                <th class="text-center">Corr.</th>
                <th class="text-center">FECHA</th>
                <th class="text-center">No. Doc.</th>
                <th class="text-left">CLIENTE</th>
                <th class="text-right">EXENTAS</th>
                <th class="text-right">NO SUJETAS</th>
                <th class="text-right">INTERNAS GRAVADAS</th>
                <th class="text-right">DEBITO FISCAL</th>
                <th class="text-right">VENTA TOTAL</th>
                <th class="text-center nowrap">NÚMERO CONTROL DTE</th>
                <th class="text-center nowrap">CÓDIGO GENERACIÓN</th>
                <th class="text-center nowrap">SELLO RECEPCIÓN</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $i = 1;
            $tot_exentas = 0.00;
            $tot_nosujetas = 0.00;
            $tot_int_grav = 0.00;
            $tot_debfiscal = 0.00;
            $tot_final = 0.00;
        ?>
        @foreach ($sales as $sale)
            <tr>
                <td class="text-center">{{ $i }}</td>
                <td class="text-center">{{ $sale['dateF'] }}</td>
                <td class="text-center small">{{ $sale['correlativo'] ?? '-' }}</td>
                <td class="text-left">
                    @if($sale['typesale']=='0')
                        ANULADO
                    @else
                        @if($sale['tpersona']=='J')
                            {{ strtoupper($sale['comercial_name']) }}
                        @else
                            {{ strtoupper($sale['firstname'] .' '. $sale['firstlastname']) }}
                        @endif
                    @endif
                </td>
                @if($sale['typesale']=='0')
                    <td class="text-center">ANULADO</td>
                    <td class="text-center">ANULADO</td>
                    <td class="text-center">ANULADO</td>
                    <td class="text-center">ANULADO</td>
                    <td class="text-center">ANULADO</td>
                @else
                    <td class="text-right">{{ number_format($sale['exenta'], 2) }}</td>
                    <td class="text-right">{{ number_format($sale['nosujeta'], 2) }}</td>
                    <td class="text-right">{{ number_format($sale['gravada'], 2) }}</td>
                    <td class="text-right">{{ number_format($sale['iva'], 2) }}</td>
                    <td class="text-right">{{ number_format($sale['totalamount'], 2) }}</td>
                    <?php
                        $tot_exentas += $sale['exenta'];
                        $tot_nosujetas += $sale['nosujeta'];
                        $tot_int_grav += $sale['gravada'];
                        $tot_debfiscal += $sale['iva'];
                        $tot_final += $sale['totalamount'];
                    ?>
                @endif
                <td class="text-center small nowrap">{{ $sale['numeroControl'] ?? '-' }}</td>
                <td class="text-center small nowrap">{{ $sale['codigoGeneracion'] ?? '-' }}</td>
                <td class="text-center small nowrap">{{ $sale['selloRecibido'] ?? '-' }}</td>
            </tr>
            <?php $i++; ?>
        @endforeach
        <tr style="font-weight: bold;">
            <td class="text-right" colspan="4">TOTALES DEL MES</td>
            <td class="text-right">{{ number_format($tot_exentas, 2) }}</td>
            <td class="text-right">{{ number_format($tot_nosujetas, 2) }}</td>
            <td class="text-right">{{ number_format($tot_int_grav, 2) }}</td>
            <td class="text-right">{{ number_format($tot_debfiscal, 2) }}</td>
            <td class="text-right">{{ number_format($tot_final, 2) }}</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
        </tr>
        </tbody>
    </table>

    <br>
    <table class="no-border" style="width: 100%; border: 0;">
        <tr class="no-border">
            <td class="no-border text-center" colspan="12"><strong>LIQUIDACION DEL DEBITO FISCAL EN VENTAS DIRECTAS</strong></td>
        </tr>
        <tr class="no-border">
            <td class="no-border text-right" colspan="6"><strong>GRAVADAS, NO SUJETAS, EXENTAS, SIN IVA</strong></td>
            <td class="no-border text-right" colspan="2">{{ number_format($tot_int_grav + $tot_exentas + $tot_nosujetas, 2) }}</td>
            <td class="no-border" colspan="4"></td>
        </tr>
        <tr class="no-border">
            <td class="no-border" colspan="3">VENTAS EXENTAS</td>
            <td class="no-border text-right">{{ number_format($tot_exentas, 2) }}</td>
            <td class="no-border text-right">13 %</td>
            <td class="no-border text-right">{{ number_format($tot_debfiscal, 2) }}</td>
            <td class="no-border" colspan="6"></td>
        </tr>
        <tr class="no-border">
            <td class="no-border" colspan="3">VENTAS NO SUJETAS</td>
            <td class="no-border text-right">{{ number_format($tot_nosujetas, 2) }}</td>
            <td class="no-border text-right">0 %</td>
            <td class="no-border text-right">0.00</td>
            <td class="no-border" colspan="6"></td>
        </tr>
        <tr class="no-border" style="font-weight: bold;">
            <td class="no-border" colspan="3">VENTA LOCALES GRAVADAS</td>
            <td class="no-border text-right">{{ number_format($tot_int_grav, 2) }}</td>
            <td class="no-border text-right">TOTAL</td>
            <td class="no-border text-right">{{ number_format($tot_int_grav + $tot_debfiscal, 2) }}</td>
            <td class="no-border" colspan="6"></td>
        </tr>
    </table>
</main>

</body>
</html>


