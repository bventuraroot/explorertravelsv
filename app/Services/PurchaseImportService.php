<?php

namespace App\Services;

use App\Models\EmailPurchaseImport;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseImportService
{
    public function process(EmailPurchaseImport $import, array $parsed): void
    {
        DB::transaction(function () use ($import, $parsed) {
            $header = $parsed['header'];

            // Anti-duplicidad física por UUID (codigoGeneracion)
            if ($import->dte_codigo_generacion) {
                $existing = Purchase::where('codigo_generacion', $import->dte_codigo_generacion)->first();
                if ($existing) {
                    $import->update([
                        'purchase_id' => $existing->id,
                        'status'      => 'processed',
                        'processed_at' => now(),
                    ]);
                    return;
                }
            }

            // Si el proveedor y la empresa receptora son completamente conocidos,
            // creamos la compra física automáticamente en estado procesado.
            if ($header['provider_id'] && $header['company_id']) {
                $purchase = Purchase::create([
                    'document_id'       => $header['document_id'],
                    'provider_id'       => $header['provider_id'],
                    'company_id'        => $header['company_id'],
                    'number'            => $header['number'],
                    'date'              => $header['date'],
                    'exenta'            => $header['exenta'],
                    'gravada'           => $header['gravada'],
                    'iva'               => $header['iva'],
                    'contrns'           => $header['contrns'],
                    'fovial'            => $header['fovial'],
                    'iretenido'         => $header['iretenido'],
                    'otros'             => $header['otros'],
                    'total'             => $header['total'],
                    'fingreso'          => $header['fingreso'],
                    'periodo'           => $header['periodo'],
                    'user_id'           => $header['user_id'],
                    'import_id'         => $import->id,
                    'codigo_generacion' => $import->dte_codigo_generacion,
                    'sello_recepcion'   => $import->dte_sello_recepcion,
                ]);

                $import->update([
                    'purchase_id'  => $purchase->id,
                    'status'       => 'processed',
                    'processed_at' => now(),
                ]);
            } else {
                // Si falta resolución, se queda en pending para mapeo manual
                $import->update([
                    'status' => 'pending',
                ]);
            }
        });
    }

    public function confirmPurchaseManual(EmailPurchaseImport $import, int $providerId, int $companyId, int $userId): Purchase
    {
        if ($import->status === 'processed') {
            throw new \RuntimeException("Esta importación ya fue procesada.");
        }

        return DB::transaction(function () use ($import, $providerId, $companyId, $userId) {
            $dte = json_decode($import->raw_json, true);
            $parsed = app(DtePurchaseParser::class)->parse($dte, \App\Models\Company::findOrFail($companyId), $userId);
            $header = $parsed['header'];

            $purchase = Purchase::create([
                'document_id'       => $header['document_id'],
                'provider_id'       => $providerId,
                'company_id'        => $companyId,
                'number'            => $header['number'],
                'date'              => $header['date'],
                'exenta'            => $header['exenta'],
                'gravada'           => $header['gravada'],
                'iva'               => $header['iva'],
                'contrns'           => $header['contrns'],
                'fovial'            => $header['fovial'],
                'iretenido'         => $header['iretenido'],
                'otros'             => $header['otros'],
                'total'             => $header['total'],
                'fingreso'          => $header['fingreso'],
                'periodo'           => $header['periodo'],
                'user_id'           => $userId,
                'import_id'         => $import->id,
                'codigo_generacion' => $import->dte_codigo_generacion,
                'sello_recepcion'   => $import->dte_sello_recepcion,
            ]);

            $import->update([
                'purchase_id'  => $purchase->id,
                'status'       => 'processed',
                'processed_at' => now(),
            ]);

            return $purchase;
        });
    }
}
