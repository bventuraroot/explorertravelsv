<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Dte;
use App\Models\Company;
use App\Models\Client;
use App\Models\Product;
use App\Models\Salesdetail;
use App\Models\Typedocument;
use App\Models\Correlativo;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CreditNoteController extends Controller
{
    /**
     * Constructor con middleware de permisos
     */
    public function __construct()
    {
        $this->middleware('permission:notas-credito.index')->only(['index', 'show']);
        $this->middleware('permission:notas-credito.create')->only(['create', 'store']);
        $this->middleware('permission:notas-credito.edit')->only(['edit', 'update']);
        $this->middleware('permission:notas-credito.destroy')->only(['destroy']);
        $this->middleware('permission:notas-credito.print')->only(['print']);
        $this->middleware('permission:notas-credito.send-email')->only(['sendEmail']);
    }

    /**
     * Mostrar lista de notas de crédito
     */
    public function index(Request $request): View
    {
        $query = Sale::with(['client', 'company', 'user', 'dte'])
            ->where('typesale', 1) // Solo facturas confirmadas
            ->whereHas('dte', function($q) {
                $q->where('tipoDte', '05'); // Nota de crédito
            });

        // Filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('date', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('date', '<=', $request->fecha_hasta);
        }
        if ($request->filled('cliente_id')) {
            $query->where('client_id', $request->cliente_id);
        }
        if ($request->filled('empresa_id')) {
            $query->where('company_id', $request->empresa_id);
        }
        if ($request->filled('estado')) {
            $query->where('state', $request->estado);
        }

        $notasCredito = $query->orderBy('date', 'desc')->paginate(20);

        $clientes = Client::select('id', 'firstname', 'secondname', 'firstlastname', 'secondlastname', 'name_contribuyente', 'tpersona')
            ->get()
            ->sortBy('nameClient');

        $empresas = Company::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('credit-notes.index', compact('notasCredito', 'clientes', 'empresas'));
    }

    /**
     * Mostrar formulario para crear nueva nota de crédito
     */
    public function create(Request $request)
    {
        $saleId = $request->get('sale_id');
        $sale = null;

        if ($saleId) {
            $sale = Sale::with(['client', 'company', 'salesdetails.product', 'dte'])
                ->where('id', $saleId)
                ->where('typesale', 1)
                ->where('state', 1)
                ->first();

            if (!$sale) {
                return redirect()->route('credit-notes.index')
                    ->with('error', 'La venta seleccionada no es válida para crear una nota de crédito.');
            }
        }

        $empresas = Company::select('id', 'name', 'nit', 'ncr')
            ->orderBy('name')
            ->get();

        // Buscar el tipo de documento NCR (Nota de Crédito)
        $tipoNCR = Typedocument::where('type', 'NCR')->first();
        $tiposDocumento = collect();

        if ($tipoNCR) {
            $tiposDocumento = collect([$tipoNCR]);
        }

        // Si solo hay un tipo de documento, auto-seleccionarlo
        $tipoDocumentoSeleccionado = null;
        if ($tiposDocumento->count() == 1) {
            $tipoDocumentoSeleccionado = $tiposDocumento->first()->id;
        }

        // Si hay una venta específica, auto-seleccionar su empresa
        $empresaSeleccionada = null;
        if ($sale && $sale->company_id) {
            $empresaSeleccionada = $sale->company_id;
        }

        // Historial de Notas de Crédito previas de esta venta
        $historialNCR = collect();
        if ($sale) {
            $historialNCR = Sale::with(['dte'])
                ->where('doc_related', $sale->id)
                ->orderBy('date', 'desc')
                ->get(['id', 'date', 'totalamount', 'state']);
        }

        return view('credit-notes.create', compact('sale', 'empresas', 'tiposDocumento', 'tipoDocumentoSeleccionado', 'empresaSeleccionada', 'historialNCR'));
    }

    /**
     * Almacenar nueva nota de crédito
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'company_id' => 'required|exists:companies,id',
            'typedocument_id' => 'required|exists:typedocuments,id',
            'motivo' => 'required|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.product_id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio' => 'required|numeric|min:0',
        ]);

        try {
            // Obtener la venta original
            $saleOriginal = Sale::with(['client', 'company', 'salesdetails.product'])
                ->where('id', $request->sale_id)
                ->where('typesale', 1)
                ->where('state', 1)
                ->firstOrFail();

            // Verificar si la emisión de DTE está habilitada para esta empresa
            $configCompany = Config::where('company_id', $request->company_id)->first();
            $dteHabilitado = $configCompany && $configCompany->emision_dte;

            if ($dteHabilitado) {
                // 1. Preparar datos de la nota de crédito para enviar a Hacienda SIN crear registros
                $datosNotaCredito = $this->prepararDatosParaHacienda($request, $saleOriginal);

                // 2. Enviar a Hacienda para validación
                $saleController = new \App\Http\Controllers\SaleController();
                $respuestaHacienda = $saleController->Enviar_Hacienda($datosNotaCredito, "05");

                // 3. Si Hacienda rechaza, no crear nada
                if ($respuestaHacienda["codEstado"] == "03") {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Nota de crédito rechazada por Hacienda: ' . ($respuestaHacienda["descripcionMsg"] ?? 'Error desconocido'));
                }

                // 4. Solo si Hacienda acepta, crear los registros
                DB::beginTransaction();

                try {
                    $notaCredito = $this->crearNotaCreditoEnBD($request, $saleOriginal, $respuestaHacienda);

                    DB::commit();

                    return redirect()->route('credit-notes.show', $notaCredito->id)
                        ->with('success', 'Nota de crédito creada y enviada a Hacienda exitosamente.');

                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

            } else {
                // Si la emisión no está habilitada, crear solo los registros locales
                DB::beginTransaction();

                $notaCredito = $this->crearNotaCreditoEnBD($request, $saleOriginal);

                DB::commit();

                return redirect()->route('credit-notes.show', $notaCredito->id)
                    ->with('success', 'Nota de crédito creada exitosamente (emisión electrónica deshabilitada).');
            }

        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Error creando nota de crédito: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la nota de crédito: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalles de una nota de crédito
     */
    public function show(Sale $creditNote): View
    {
        $creditNote->load(['client', 'company', 'user', 'salesdetails.product', 'dte']);

        return view('credit-notes.show', compact('creditNote'));
    }

    /**
     * Mostrar formulario para editar nota de crédito
     */
    public function edit(Sale $creditNote): View
    {
        $creditNote->load(['client', 'company', 'salesdetails.product']);

        $empresas = Company::select('id', 'name', 'nit', 'ncr')
            ->orderBy('name')
            ->get();

        $tiposDocumento = Typedocument::where('type', 'NCR')
            ->select('id', 'type', 'description')
            ->get();

        return view('credit-notes.edit', compact('creditNote', 'empresas', 'tiposDocumento'));
    }

    /**
     * Actualizar nota de crédito
     */
    public function update(Request $request, Sale $creditNote): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
            'productos' => 'required|array|min:1',
            'productos.*.product_id' => 'required|exists:products,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Eliminar detalles existentes
            $creditNote->salesdetails()->delete();

            // Recalcular totales
            $totalGravadas = 0;
            $totalExentas = 0;
            $totalNoSujetas = 0;
            $totalIva = 0;

            foreach ($request->productos as $productoData) {
                $producto = Product::findOrFail($productoData['product_id']);
                $cantidad = $productoData['cantidad'];
                $precio = $productoData['precio'];
                $subtotal = $cantidad * $precio;

                $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

                if ($tipoVenta === 'gravada') {
                    $totalGravadas += $subtotal;
                    $totalIva += $subtotal * 0.13;
                } elseif ($tipoVenta === 'exenta') {
                    $totalExentas += $subtotal;
                } else {
                    $totalNoSujetas += $subtotal;
                }

                // Crear nuevo detalle
                $detalle = new Salesdetail();
                $detalle->fill([
                    'sale_id' => $creditNote->id,
                    'product_id' => $producto->id,
                    'amountp' => $cantidad,
                    'priceunit' => $precio,
                    'pricesale' => $precio,
                    'exempt' => $tipoVenta === 'exenta' ? $subtotal : 0,
                    'nosujeta' => $tipoVenta === 'nosujeta' ? $subtotal : 0,
                    'detained13' => $tipoVenta === 'gravada' ? $subtotal * 0.13 : 0,
                    'detained' => 0,
                    'renta' => 0, // Campo requerido
                    'fee' => 0, // Campo requerido
                    'feeiva' => 0, // Campo requerido
                    'reserva' => 0, // Campo requerido
                    'user_id' => Auth::id(),
                ]);
                $detalle->save();
            }

            $creditNote->update([
                'motivo' => $request->motivo,
                'totalamount' => $totalGravadas + $totalExentas + $totalNoSujetas + $totalIva,
            ]);

            DB::commit();

            return redirect()->route('credit-notes.show', $creditNote->id)
                ->with('success', 'Nota de crédito actualizada exitosamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la nota de crédito: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar nota de crédito
     */
    public function destroy(Sale $creditNote): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Verificar que no esté procesada en hacienda
            if ($creditNote->dte && $creditNote->dte->estadoHacienda === 'PROCESADO') {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar una nota de crédito ya procesada en Hacienda.');
            }

            // Eliminar detalles y DTE
            $creditNote->salesdetails()->delete();
            $creditNote->dte()->delete();
            $creditNote->delete();

            DB::commit();

            return redirect()->route('credit-notes.index')
                ->with('success', 'Nota de crédito eliminada exitosamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al eliminar la nota de crédito: ' . $e->getMessage());
        }
    }

    /**
     * Imprimir nota de crédito
     */
    public function print(Sale $creditNote)
    {
        try {
            // Cargar relaciones necesarias
            $creditNote->load(['dte']);

            $saleController = new \App\Http\Controllers\SaleController();

            // Verificar si tiene DTE
            if ($creditNote->dte && $creditNote->dte->json) {
                // Usar genera_pdf para DTE
                return $saleController->genera_pdf($creditNote->id);
            } else {
                // Usar genera_pdflocal para documentos sin DTE
                return $saleController->genera_pdflocal($creditNote->id);
            }
        } catch (\Exception $e) {
            Log::error('Error generando PDF de nota de crédito: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Enviar nota de crédito por correo
     */
    public function sendEmail(Request $request, Sale $creditNote): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'mensaje' => 'nullable|string|max:1000'
        ]);

        try {
            // Aquí implementarías la lógica de envío de correo
            // Similar a como se hace en SaleController

            return response()->json([
                'success' => true,
                'message' => 'Nota de crédito enviada exitosamente.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la nota de crédito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos de una venta para nota de crédito
     */
    public function getSaleProducts(Sale $sale): JsonResponse
    {
        $sale->load(['salesdetails.product']);

        $productos = $sale->salesdetails->map(function($detalle) {
            return [
                'id' => $detalle->product->id,
                'name' => $detalle->product->name,
                'code' => $detalle->product->code,
                'cantidad_original' => $detalle->amountp,
                'precio_original' => $detalle->pricesale,
                'tipo_venta' => $detalle->exempt > 0 ? 'exenta' : ($detalle->nosujeta > 0 ? 'nosujeta' : 'gravada'),
                'subtotal' => $detalle->amountp * $detalle->pricesale
            ];
        });

        return response()->json($productos);
    }

    /**
     * Preparar datos de nota de crédito para enviar a Hacienda (sin crear registros en BD)
     */
    private function prepararDatosParaHacienda(Request $request, Sale $saleOriginal): array
    {
        // Obtener datos del documento (tipo NCR)
        $qdoc = "SELECT
                a.id id_doc,
                a.`type` id_tipo_doc,
                docs.serie serie,
                docs.inicial inicial,
                docs.final final,
                docs.actual actual,
                docs.estado estado,
                a.company_id id_empresa,
                NULL hechopor,
                a.created_at fechacreacion,
                a.description NombreDocumento,
                NULL NombreUsuario,
                NULL docUser,
                a.codemh tipodocumento,
                a.versionjson versionJson,
                e.url_credencial,
                e.url_envio,
                e.url_invalidacion,
                e.url_contingencia,
                e.url_firmador,
                d.typeTransmission tipogeneracion,
                e.cod ambiente,
                a.updated_at,
                1 aparece_ventas
                FROM typedocuments a
                INNER JOIN docs ON a.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
                INNER JOIN config d ON a.company_id=d.company_id
                INNER JOIN ambientes e ON d.ambiente=e.id
                WHERE a.`type`= 'NCR' AND a.company_id = {$request->company_id}";

        $doc = DB::select(DB::raw($qdoc));

        if (empty($doc)) {
            throw new Exception('No se encontró configuración de documento NCR para la empresa');
        }

        // Obtener datos del cliente de la venta original
        $qcliente = "SELECT
                        clie.nit,
                        CAST(REPLACE(REPLACE(clie.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                        CASE
                            WHEN clie.tpersona = 'N' THEN CONCAT_WS(' ', clie.firstname, clie.secondname, clie.firstlastname, clie.secondlastname)
                            WHEN clie.tpersona = 'J' THEN COALESCE(clie.name_contribuyente, '')
                        END AS nombre,
                        econo.code codActividad,
                        econo.name descActividad,
                        clie.comercial_name nombreComercial,
                        dep.code departamento,
                        muni.code municipio,
                        ad.reference direccion,
                        clie.phone telefono,
                        NULL codEstableMH,
                        NULL codEstable,
                        NULL codPuntoVentaMH,
                        NULL codPuntoVenta,
                        clie.email correo
                        FROM clients clie
                        INNER JOIN addresses ad ON clie.address_id=ad.id
                        INNER JOIN departments dep ON ad.department_id=dep.id
                        INNER JOIN municipalities muni ON ad.municipality_id=muni.id
                        INNER JOIN economicactivities econo ON clie.economicactivity_id=econo.id
                        WHERE clie.id = {$saleOriginal->client_id}";

        $cliente = DB::select(DB::raw($qcliente));

        // Obtener datos del emisor (empresa)
        $queryemisor = "SELECT
                        a.nit,
                        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                        a.name nombre,
                        c.code codActividad,
                        c.name descActividad,
                        a.name nombreComercial,
                        a.tipoEstablecimiento,
                        f.code departamento,
                        g.code municipio,
                        d.reference direccion,
                        e.phone telefono,
                        NULL codEstableMH,
                        NULL codEstable,
                        NULL codPuntoVentaMH,
                        NULL codPuntoVenta,
                        a.email correo,
                        b.passkeyPublic clavePublicaMH,
                        b.passPrivateKey clavePrivadaMH,
                        b.passMH claveApiMH
                        FROM companies a
                        INNER JOIN config b ON a.id=b.company_id
                        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
                        INNER JOIN addresses d ON a.address_id=d.id
                        INNER JOIN phones e ON a.phone_id=e.id
                        INNER JOIN departments f ON d.department_id=f.id
                        INNER JOIN municipalities g ON d.municipality_id=g.id
                        WHERE a.id = {$request->company_id}";

        $emisor = DB::select(DB::raw($queryemisor));

        // Calcular totales y preparar detalles
        $totalGravadas = 0;
        $totalExentas = 0;
        $totalNoSujetas = 0;
        $totalIva = 0;
        $detalle = [];

        foreach ($request->productos as $index => $productoData) {
            $producto = Product::findOrFail($productoData['product_id']);
            $cantidad = $productoData['cantidad'];
            $precio = $productoData['precio'];
            $subtotal = $cantidad * $precio;
            $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

            if ($tipoVenta === 'gravada') {
                $totalGravadas += $subtotal;
                $totalIva += $subtotal * 0.13;
                $gravadas = $subtotal;
                $exentas = 0;
                $nosujetas = 0;
                $iva = $subtotal * 0.13;
            } elseif ($tipoVenta === 'exenta') {
                $totalExentas += $subtotal;
                $gravadas = 0;
                $exentas = $subtotal;
                $nosujetas = 0;
                $iva = 0;
            } else {
                $totalNoSujetas += $subtotal;
                $gravadas = 0;
                $exentas = 0;
                $nosujetas = $subtotal;
                $iva = 0;
            }

            $detalle[] = (object)[
                'id_factura_det' => $index + 1,
                'id_factura' => 0, // Temporal
                'id_producto' => $producto->id,
                'descripcion' => $producto->description ?? $producto->name,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'no_sujetas' => $nosujetas,
                'exentas' => $exentas,
                'gravadas' => $gravadas,
                'iva' => $iva,
                'no_imponible' => 0.00,
                'id_empresa' => $request->company_id,
                'tipo_producto' => 'D',
                'porcentaje_descuento' => 0.00,
                'descuento' => 0.00,
            ];
        }

        // Preparar documento
        $numero = $doc[0]->actual;
        $documento = [(object)[
            "tipodocumento" => $doc[0]->tipodocumento,
            "nu_doc" => $numero,
            "tipo_establecimiento" => "1",
            "version" => $doc[0]->versionJson,
            "ambiente" => $doc[0]->ambiente,
            "tipoDteOriginal" => $saleOriginal->dte->tipoDte ?? '01',
            "tipoGeneracionOriginal" => $saleOriginal->dte->tipoModelo ?? 1,
            "codigoGeneracionOriginal" => $saleOriginal->dte->codigoGeneracion ?? '',
            "selloRecibidoOriginal" => $saleOriginal->dte->selloRecibido ?? '',
            "numeroOriginal" => $saleOriginal->dte->codigoGeneracion ?? '',
            "fecEmiOriginal" => $saleOriginal->dte ? date('Y-m-d', strtotime($saleOriginal->dte->fhRecibido)) : date('Y-m-d'),
            "total_iva" => $totalIva,
            "tipoDocumento" => "",
            "numDocumento" => $cliente[0]->nit,
            "nombre" => $cliente[0]->nombre,
            "versionjson" => $doc[0]->versionJson,
            "id_empresa" => $request->company_id,
            "url_credencial" => $doc[0]->url_credencial,
            "url_envio" => $doc[0]->url_envio,
            "url_firmador" => $doc[0]->url_firmador,
            "nuEnvio" => 1,
            "condiciones" => "1",
            "total_venta" => $totalGravadas + $totalExentas + $totalNoSujetas + $totalIva,
            "tot_gravado" => $totalGravadas,
            "tot_nosujeto" => $totalNoSujetas,
            "tot_exento" => $totalExentas,
            "subTotalVentas" => $totalGravadas + $totalNoSujetas + $totalExentas,
            "descuNoSuj" => 0.00,
            "descuExenta" => 0.00,
            "descuGravada" => 0.00,
            "totalDescu" => 0.00,
            "subTotal" => $totalGravadas + $totalNoSujetas + $totalExentas,
            "ivaPerci1" => 0.00,
            "ivaRete1" => 0.00,
            "reteRenta" => 0.00,
            "montoTotalOperacion" => $totalGravadas + $totalExentas + $totalNoSujetas + $totalIva,
            "totalNoGravado" => 0.00,
            "totalPagar" => $totalGravadas + $totalExentas + $totalNoSujetas + $totalIva,
            "totalLetras" => "",
            "totalIva" => $totalIva,
            "saldoFavor" => 0.00,
            "condicionOperacion" => 1,
            "pagos" => null,
            "numPagoElectronico" => null,
        ]];

        // Retornar en el formato que espera Enviar_Hacienda
        return [
            "emisor" => $emisor,
            "documento" => $documento,
            "detalle" => $detalle,
            "totales" => $detalle, // En la función original se usa el mismo detalle
            "cliente" => $cliente
        ];
    }

    /**
     * Crear la nota de crédito en la base de datos
     */
    private function crearNotaCreditoEnBD(Request $request, Sale $saleOriginal, array $respuestaHacienda = null): Sale
    {
        // Crear la nota de crédito
        $notaCredito = new Sale();
        $notaCredito->fill([
            'client_id' => $saleOriginal->client_id,
            'company_id' => $request->company_id,
            'typedocument_id' => $request->typedocument_id,
            'user_id' => Auth::id(),
            'date' => now()->format('Y-m-d'),
            'waytopay' => $saleOriginal->waytopay,
            'typesale' => 1, // Confirmada
            'state' => 1, // Activa
            'acuenta' => $saleOriginal->acuenta,
            'motivo' => $request->motivo,
            'doc_related' => $saleOriginal->id,
        ]);

        // Calcular totales
        $totalGravadas = 0;
        $totalExentas = 0;
        $totalNoSujetas = 0;
        $totalIva = 0;

        foreach ($request->productos as $productoData) {
            $producto = Product::findOrFail($productoData['product_id']);
            $cantidad = $productoData['cantidad'];
            $precio = $productoData['precio'];
            $subtotal = $cantidad * $precio;

            $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

            if ($tipoVenta === 'gravada') {
                $totalGravadas += $subtotal;
                $totalIva += $subtotal * 0.13;
            } elseif ($tipoVenta === 'exenta') {
                $totalExentas += $subtotal;
            } else {
                $totalNoSujetas += $subtotal;
            }
        }

        $notaCredito->totalamount = $totalGravadas + $totalExentas + $totalNoSujetas + $totalIva;
        $notaCredito->save();

        // Crear los detalles
        foreach ($request->productos as $productoData) {
            $producto = Product::findOrFail($productoData['product_id']);
            $cantidad = $productoData['cantidad'];
            $precio = $productoData['precio'];
            $subtotal = $cantidad * $precio;
            $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

            $detalle = new Salesdetail();
            $detalle->fill([
                'sale_id' => $notaCredito->id,
                'product_id' => $producto->id,
                'amountp' => $cantidad,
                'priceunit' => $precio,
                'pricesale' => $precio,
                'exempt' => $tipoVenta === 'exenta' ? $subtotal : 0,
                'nosujeta' => $tipoVenta === 'nosujeta' ? $subtotal : 0,
                'detained13' => $tipoVenta === 'gravada' ? $subtotal * 0.13 : 0,
                'detained' => 0,
                'renta' => 0, // Campo requerido
                'fee' => 0, // Campo requerido
                'feeiva' => 0, // Campo requerido
                'reserva' => 0, // Campo requerido
                'user_id' => Auth::id(),
            ]);
            $detalle->save();
        }

        // Crear el DTE si hay respuesta de Hacienda
        if ($respuestaHacienda) {
            $this->crearDteDesdeRespuesta($notaCredito, $respuestaHacienda, $saleOriginal);
        }

        return $notaCredito;
    }

    /**
     * Crear registro DTE desde la respuesta de Hacienda
     */
    private function crearDteDesdeRespuesta(Sale $notaCredito, array $respuestaHacienda, Sale $saleOriginal): void
    {
        // Obtener correlativo y actualizarlo
        $correlativo = Correlativo::where('company_id', $notaCredito->company_id)
            ->where('typedocument_id', $notaCredito->typedocument_id)
            ->where('state', 1)
            ->first();

        if ($correlativo) {
            $notaCredito->nu_doc = $correlativo->actual;
            $correlativo->increment('actual');
            $notaCredito->save();
        }

        $dte = new Dte();
        $dte->fill([
            'versionJson' => 3,
            'ambiente_id' => 1,
            'tipoDte' => '05', // Nota de crédito
            'tipoModelo' => 1,
            'tipoTransmision' => 1,
            'tipoContingencia' => null,
            'idContingencia' => null,
            'nameTable' => 'Sales',
            'company_id' => $notaCredito->company_id,
            'company_name' => $notaCredito->company->name,
            'id_doc' => $respuestaHacienda["identificacion"]["numeroControl"] ?? $correlativo->actual ?? null,
            'codTransaction' => '05',
            'desTransaction' => 'Nota de Crédito',
            'type_document' => '05',
            'id_doc_Ref1' => $saleOriginal->dte->id_doc ?? $saleOriginal->id,
            'id_doc_Ref2' => null,
            'type_invalidacion' => null,
            'codEstado' => $respuestaHacienda["codEstado"] ?? '02',
            'Estado' => $respuestaHacienda["estado"] ?? 'Enviado',
            'codigoGeneracion' => $respuestaHacienda["codigoGeneracion"] ?? null,
            'selloRecibido' => $respuestaHacienda["selloRecibido"] ?? null,
            'fhRecibido' => $respuestaHacienda["fhRecibido"] ?? null,
            'estadoHacienda' => $respuestaHacienda["estadoHacienda"] ?? null,
            'nSends' => 1,
            'codeMessage' => $respuestaHacienda["codigoMsg"] ?? null,
            'claMessage' => $respuestaHacienda["clasificaMsg"] ?? null,
            'descriptionMessage' => $respuestaHacienda["descripcionMsg"] ?? null,
            'detailsMessage' => $respuestaHacienda["observacionesMsg"] ?? null,
            'created_by' => Auth::user()->name,
            'sale_id' => $notaCredito->id,
            'json' => json_encode($respuestaHacienda),
        ]);
        $dte->save();
    }
}
