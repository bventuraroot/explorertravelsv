<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contingencias', function (Blueprint $table) {
            // Verificar si la tabla existe, si no, crearla con la estructura completa
            if (!Schema::hasTable('contingencias')) {
                // Crear la tabla completa si no existe
                $table->id();
                $table->string('codInterno')->unique()->nullable();
                $table->unsignedBigInteger('idEmpresa');
                $table->string('versionJson')->nullable();
                $table->string('ambiente')->nullable();
                $table->string('codEstado')->default('01');
                $table->string('estado')->default('En Cola');
                $table->string('tipoContingencia')->nullable();
                $table->text('motivoContingencia')->nullable();
                $table->string('nombreResponsable')->nullable();
                $table->string('tipoDocResponsable')->nullable();
                $table->string('nuDocResponsable')->nullable();
                $table->datetime('fechaCreacion')->nullable();
                $table->date('fInicio')->nullable();
                $table->date('fFin')->nullable();
                $table->time('horaCreacion')->nullable();
                $table->time('hInicio')->nullable();
                $table->time('hFin')->nullable();
                $table->string('codigoGeneracion')->nullable();
                $table->string('selloRecibido')->nullable();
                $table->datetime('fhRecibido')->nullable();
                $table->string('codEstadoHacienda')->nullable();
                $table->string('estadoHacienda')->nullable();
                $table->string('codigoMsg')->nullable();
                $table->string('clasificaMsg')->nullable();
                $table->text('descripcionMsg')->nullable();
                $table->text('observacionesMsg')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                // Índices
                $table->index(['idEmpresa', 'codEstado']);
                $table->index(['fInicio', 'fFin']);
                $table->index('codigoGeneracion');

                // Claves foráneas
                $table->foreign('idEmpresa')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            } else {
                // Solo agregar columnas que falten si la tabla ya existe
                $columnsToAdd = [
                    'versionJson' => 'string',
                    'ambiente' => 'string',
                    'codEstado' => 'string',
                    'estado' => 'string',
                    'tipoContingencia' => 'string',
                    'motivoContingencia' => 'text',
                    'nombreResponsable' => 'string',
                    'tipoDocResponsable' => 'string',
                    'nuDocResponsable' => 'string',
                    'fechaCreacion' => 'datetime',
                    'horaCreacion' => 'time',
                    'hInicio' => 'time',
                    'hFin' => 'time',
                    'codigoGeneracion' => 'string',
                    'selloRecibido' => 'string',
                    'fhRecibido' => 'datetime',
                    'codEstadoHacienda' => 'string',
                    'estadoHacienda' => 'string',
                    'codigoMsg' => 'string',
                    'clasificaMsg' => 'string',
                    'descripcionMsg' => 'text',
                    'created_by' => 'unsignedBigInteger',
                    'updated_by' => 'unsignedBigInteger'
                ];

                foreach ($columnsToAdd as $column => $type) {
                    if (!Schema::hasColumn('contingencias', $column)) {
                        switch ($type) {
                            case 'string':
                                $table->string($column)->nullable();
                                break;
                            case 'text':
                                $table->text($column)->nullable();
                                break;
                            case 'datetime':
                                $table->datetime($column)->nullable();
                                break;
                            case 'time':
                                $table->time($column)->nullable();
                                break;
                            case 'unsignedBigInteger':
                                $table->unsignedBigInteger($column)->nullable();
                                break;
                        }
                    }
                }

                // Agregar índices si no existen
                try {
                    $table->index(['idEmpresa', 'codEstado']);
                } catch (Exception $e) {
                    // El índice ya existe
                }

                try {
                    $table->index(['fInicio', 'fFin']);
                } catch (Exception $e) {
                    // El índice ya existe
                }

                try {
                    $table->index('codigoGeneracion');
                } catch (Exception $e) {
                    // El índice ya existe
                }

                // Agregar claves foráneas si no existen
                try {
                    $table->foreign('idEmpresa')->references('id')->on('companies')->onDelete('cascade');
                } catch (Exception $e) {
                    // La clave foránea ya existe
                }

                try {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                } catch (Exception $e) {
                    // La clave foránea ya existe
                }

                try {
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                } catch (Exception $e) {
                    // La clave foránea ya existe
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contingencias', function (Blueprint $table) {
            // Solo eliminar las columnas que agregamos, no la tabla completa
            $columnsToRemove = [
                'versionJson', 'ambiente', 'codEstado', 'estado', 'tipoContingencia',
                'motivoContingencia', 'nombreResponsable', 'tipoDocResponsable',
                'nuDocResponsable', 'fechaCreacion', 'horaCreacion', 'hInicio', 'hFin',
                'codigoGeneracion', 'selloRecibido', 'fhRecibido', 'codEstadoHacienda',
                'estadoHacienda', 'codigoMsg', 'clasificaMsg', 'descripcionMsg',
                'created_by', 'updated_by'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('contingencias', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Eliminar índices
            try {
                $table->dropIndex(['idEmpresa', 'codEstado']);
            } catch (Exception $e) {
                // El índice no existe
            }

            try {
                $table->dropIndex(['fInicio', 'fFin']);
            } catch (Exception $e) {
                // El índice no existe
            }

            try {
                $table->dropIndex(['codigoGeneracion']);
            } catch (Exception $e) {
                // El índice no existe
            }

            // Eliminar claves foráneas
            try {
                $table->dropForeign(['idEmpresa']);
            } catch (Exception $e) {
                // La clave foránea no existe
            }

            try {
                $table->dropForeign(['created_by']);
            } catch (Exception $e) {
                // La clave foránea no existe
            }

            try {
                $table->dropForeign(['updated_by']);
            } catch (Exception $e) {
                // La clave foránea no existe
            }
        });
    }
};
