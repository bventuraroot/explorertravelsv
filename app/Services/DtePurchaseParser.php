<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Provider;
use App\Models\Typedocument;

class DtePurchaseParser
{
    private const TIPO_DTE_NOMBRES = [
        '01' => 'Factura',
        '03' => 'Comprobante de Crédito Fiscal',
        '05' => 'Nota de Crédito',
        '06' => 'Nota de Débito',
        '14' => 'Factura de Sujeto Excluido',
    ];

    public function parse(array $dte, Company $company, int $userId): array
    {
        $identificacion = $dte['identificacion'];
        $emisor         = $dte['emisor'];
        $resumen        = $dte['resumen'];
        $tipoDte        = $identificacion['tipoDte'];

        $tributos = $this->extractTributos($resumen);
        $total    = (float) ($resumen['montoTotalOperacion'] ?? $resumen['totalPagar'] ?? 0);

        $header = [
            'document_id'         => $this->resolveDocumentType($tipoDte),
            'document_tipo_dte'  => $tipoDte,
            'related_purchase_id'=> $this->resolveRelatedPurchase($dte, $tipoDte),
            'provider_id' => $this->resolveProvider($emisor),
            'company_id'  => $company->id,
            'number'      => $identificacion['numeroControl'],
            'date'        => $identificacion['fecEmi'],
            'exenta'      => (float) ($resumen['totalExenta'] ?? 0),
            'gravada'     => (float) ($resumen['totalGravada'] ?? 0),
            'iva'         => $tributos['iva'],
            'iretenido'   => (float) ($resumen['ivaRete1'] ?? 0),
            'contrns'     => (float) ($resumen['reteRenta'] ?? 0),
            'fovial'      => $tributos['fovial'],
            'cesc'        => $tributos['cesc'],
            'descuentos'  => (float) ($resumen['totalDescu'] ?? 0),
            'otros'       => (float) ($resumen['totalNoSuj'] ?? 0),
            'total'       => $total,
            'periodo'     => date('m', strtotime($identificacion['fecEmi'])), // Formato de 2 dígitos del mes según view (ej: "05")
            'fingreso'    => date('Y-m-d'),
            'user_id'     => $userId,
        ];

        return [
            'header'            => $header,
            'dte_tipo_dte'      => $tipoDte,
            'dte_tipo_nombre'   => self::TIPO_DTE_NOMBRES[$tipoDte] ?? "Tipo {$tipoDte}",
            'codigo_generacion' => $identificacion['codigoGeneracion'],
            'numero_control'    => $identificacion['numeroControl'],
        ];
    }

    private function resolveDocumentType(string $tipoDte): int
    {
        return match ($tipoDte) {
            '01' => 6,       // FACTURA local ID en view
            '03' => 3,       // COMPROBANTE DE CREDITO FISCAL local ID en view
            '05', '06' => 9, // NOTA DE CREDITO local ID en view
            default => 6,
        };
    }

    public function resolveProvider(array $emisor): int
    {
        $nit    = trim($emisor['nit'] ?? '');
        $nrc    = trim($emisor['nrc'] ?? '');
        $nombre = trim($emisor['nombre'] ?? '');

        if ($nit) {
            $provider = Provider::whereRaw("REPLACE(REPLACE(nit, '-', ''), ' ', '') = ?", [
                preg_replace('/[\s\-]/', '', $nit)
            ])->first();
            if ($provider) return $provider->id;
        }
        if ($nrc) {
            $provider = Provider::whereRaw("REPLACE(REPLACE(ncr, '-', ''), ' ', '') = ?", [
                preg_replace('/[\s\-]/', '', $nrc)
            ])->first();
            if ($provider) return $provider->id;
            
            $provider = Provider::whereRaw("REPLACE(REPLACE(nrc, '-', ''), ' ', '') = ?", [
                preg_replace('/[\s\-]/', '', $nrc)
            ])->first();
            if ($provider) return $provider->id;
        }
        if ($nombre) {
            $provider = Provider::whereRaw('LOWER(razonsocial) LIKE ?', ['%' . strtolower($nombre) . '%'])->first();
            if ($provider) return $provider->id;
        }

        throw new \RuntimeException("El proveedor emisor (NIT: {$nit}) no coincide con ningún proveedor registrado en el catálogo local.");
    }

    private function extractTributos(array $resumen): array
    {
        $result = ['iva' => 0.0, 'fovial' => 0.0, 'cesc' => 0.0];
        if (empty($resumen['tributos']) || !is_array($resumen['tributos'])) return $result;

        foreach ($resumen['tributos'] as $tributo) {
            $codigo = strtoupper(trim($tributo['codigo'] ?? ''));
            $valor  = (float) ($tributo['valor'] ?? 0);

            match ($codigo) {
                '20' => $result['iva']    = $valor,
                'D1' => $result['fovial'] = $valor,
                'C8' => $result['cesc']   = $valor,
                default => null,
            };
        }
        return $result;
    }

    /**
     * Resolver la compra original referenciada en una NC\/ND de proveedor.
     */
    private function resolveRelatedPurchase(array $dte, string $tipoDte): ?int
    {
        if (!in_array($tipoDte, ['05', '06'])) return null;

        $docRelacionado = $dte['documentoRelacionado'] ?? $dte['docRelacionado'] ?? null;
        if (!$docRelacionado) return null;

        $codigoGeneracion = null;
        if (is_array($docRelacionado)) {
            $first = isset($docRelacionado[0]) ? $docRelacionado[0] : $docRelacionado;
            $codigoGeneracion = $first['codigoGeneracion'] ?? $first['codigogeneracion'] ?? $first['numeroDocumento'] ?? $first['numerodocumento'] ?? null;
        }

        if (!$codigoGeneracion) return null;

        return Purchase::where('codigo_generacion', $codigoGeneracion)->value('id');
    }
}
