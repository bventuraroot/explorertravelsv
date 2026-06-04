<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailPurchaseImport;
use App\Models\Purchase;
use App\Models\Provider;

class DteValidator
{
    private const TIPO_DOCUMENTO_NIT_EXACTO = ['36']; // NIT jurídico

    public function validate(array $dte, string $emailUid): array
    {
        // 1. Estructura básica obligatoria del DTE
        $structureError = $this->validateStructure($dte);
        if ($structureError) {
            return ['valid' => false, 'should_skip' => false, 'errors' => [$structureError], 'company' => null];
        }

        // 2. Omitir ambientes de prueba en base de datos de producción
        $ambiente = $dte['identificacion']['ambiente'] ?? $dte['ambiente'] ?? '';
        if ($ambiente && $ambiente !== '01') {
            return ['valid' => false, 'should_skip' => true, 'errors' => ["DTE en ambiente de pruebas (ambiente={$ambiente})."], 'company' => null];
        }

        // 3. Validar estado de Hacienda (si tiene bloque de respuesta)
        $estadoMH = $dte['respuestaMH']['estado'] ?? $dte['responseMH']['estado'] ?? $dte['estado'] ?? '';
        $selloMH  = $dte['respuestaMH']['selloRecibido'] ?? $dte['responseMH']['selloRecibido'] ?? $dte['selloRecibido'] ?? '';

        if (($estadoMH || $selloMH) && $estadoMH !== 'PROCESADO' && !$selloMH) {
            return ['valid' => false, 'should_skip' => false, 'errors' => ["DTE sin validación por Hacienda."], 'company' => null];
        }

                // 4. Solo procesar tipos DTE validos para compras de proveedor
        $tipoDte = $dte['identificacion']['tipoDte'] ?? '';
        $tiposValidos = ['01', '03', '05', '06', '14'];
        if ($tipoDte && !in_array($tipoDte, $tiposValidos)) {
            return ['valid' => false, 'should_skip' => true, 'errors' => ["Tipo DTE {$tipoDte} no corresponde a un documento de compra de proveedor."], 'company' => null];
        }

        // 5. Regla Anti-Duplicado: Codigo de Generacion unico
        $codigoGeneracion = $dte['identificacion']['codigoGeneracion'] ?? '';
        if ($codigoGeneracion && EmailPurchaseImport::where('dte_codigo_generacion', $codigoGeneracion)->exists()) {
            return ['valid' => false, 'should_skip' => true, 'errors' => ["DTE ya procesado anteriormente (codigoGeneracion: {$codigoGeneracion})."], 'company' => null];
        }

        // 5. Verificar existencia del Proveedor (Emisor)
        $emisor = $dte['emisor'] ?? [];
        $provider = $this->findProvider($emisor);
        if (!$provider) {
            $nit = trim($emisor['nit'] ?? '');
            return ['valid' => false, 'should_skip' => false, 'errors' => ["El proveedor emisor (NIT: {$nit}) no se encuentra registrado en el catálogo local."], 'company' => null];
        }

        // 6. Regla Anti-Duplicado: Control + Proveedor en compras registradas manualmente
        $numeroControl = $dte['identificacion']['numeroControl'] ?? '';
        if ($numeroControl && Purchase::where('provider_id', $provider->id)->where('number', $numeroControl)->exists()) {
            return ['valid' => false, 'should_skip' => true, 'errors' => ["La compra bajo control {$numeroControl} ya está ingresada en el sistema."], 'company' => null];
        }

        // 7. Resolver Empresa Receptora
        $receptorNit    = trim($dte['receptor']['nit'] ?? '');
        $receptorNrc    = trim($dte['receptor']['nrc'] ?? '');
        $tipoDocumento  = $dte['receptor']['tipoDocumento'] ?? '';
        $numDocumento   = trim($dte['receptor']['numDocumento'] ?? '');
        $receptorEmail  = trim($dte['receptor']['correo'] ?? '');
        $receptorNombre = trim($dte['receptor']['nombre'] ?? '');

        if (!$receptorNit && in_array($tipoDocumento, self::TIPO_DOCUMENTO_NIT_EXACTO, true)) {
            $receptorNit = $numDocumento;
        }
        if (!$receptorNit && $numDocumento) {
            $receptorNit = $numDocumento;
        }

        $company = $this->findCompany($receptorNit, $receptorNrc, $receptorEmail, $receptorNombre);

        if (!$company) {
            return [
                'valid'       => false,
                'should_skip' => false,
                'errors'      => ["El receptor del DTE (NIT: {$receptorNit} / NRC: {$receptorNrc}) no coincide con ninguna empresa configurada."],
                'company'     => null
            ];
        }

        return ['valid' => true, 'should_skip' => false, 'errors' => [], 'company' => $company];
    }

    private function validateStructure(array $dte): ?string
    {
        $required = ['identificacion', 'emisor', 'receptor', 'cuerpoDocumento', 'resumen'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $dte)) {
                return "Falta bloque obligatorio: {$key}";
            }
        }
        return null;
    }

    private function findCompany(string $nit, string $nrc, string $email = '', string $nombre = ''): ?Company
    {
        $nitClean = preg_replace('/[\s\-]/', '', $nit);
        $nrcClean = preg_replace('/[\s\-]/', '', $nrc);

        if ($nitClean) {
            $company = Company::whereRaw("REPLACE(REPLACE(nit, '-', ''), ' ', '') = ?", [$nitClean])->first();
            if ($company) return $company;
        }
        if ($nrcClean) {
            $company = Company::whereRaw("REPLACE(REPLACE(ncr, '-', ''), ' ', '') = ?", [$nrcClean])->first();
            if ($company) return $company;
            
            $company = Company::whereRaw("REPLACE(REPLACE(nrc, '-', ''), ' ', '') = ?", [$nrcClean])->first();
            if ($company) return $company;
        }
        if ($email && str_contains($email, '@')) {
            $domain = substr($email, strrpos($email, '@') + 1);
            $company = Company::where('email', 'like', '%@' . $domain)->first();
            if ($company) return $company;
        }
        if (strlen($nombre) > 5) {
            $company = Company::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($nombre) . '%'])->first();
            if ($company) return $company;
        }
        return null;
    }

    private function findProvider(array $emisor): ?Provider
    {
        $nit    = trim($emisor['nit'] ?? '');
        $nrc    = trim($emisor['nrc'] ?? '');
        $nombre = trim($emisor['nombre'] ?? '');

        if ($nit) {
            $provider = Provider::whereRaw("REPLACE(REPLACE(nit, '-', ''), ' ', '') = ?", [
                preg_replace('/[\s\-]/', '', $nit)
            ])->first();
            if ($provider) return $provider;
        }
        if ($nrc) {
            $provider = Provider::whereRaw("REPLACE(REPLACE(ncr, '-', ''), ' ', '') = ?", [
                preg_replace('/[\s\-]/', '', $nrc)
            ])->first();
            if ($provider) return $provider;
            
            $provider = Provider::whereRaw("REPLACE(REPLACE(nrc, '-', ''), ' ', '') = ?", [
                preg_replace('/[\s\-]/', '', $nrc)
            ])->first();
            if ($provider) return $provider;
        }
        if ($nombre) {
            $provider = Provider::whereRaw('LOWER(razonsocial) LIKE ?', ['%' . strtolower($nombre) . '%'])->first();
            if ($provider) return $provider;
        }
        return null;
    }
}
