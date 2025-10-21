<!-- Modal de Detalles del Cliente -->
<div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="clientDetailsModalLabel">
                    <i class="fas fa-user me-2"></i>Detalles del Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Información Personal -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-clipboard-list text-white"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-primary">Información Personal</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">TIPO DE PERSONA</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientTypePerson">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">PRIMER NOMBRE</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientFirstName">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">SEGUNDO NOMBRE</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientSecondName">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">PRIMER APELLIDO</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientFirstLastName">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">SEGUNDO APELLIDO</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientSecondLastName">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="fas fa-phone text-white"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-success">Información de Contacto</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">EMAIL</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientEmail">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">TELÉFONO CELULAR</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientPhone">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">TELÉFONO FIJO</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientPhoneFixed">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">DIRECCIÓN</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientAddress">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Fiscal -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning rounded-circle p-2 me-3">
                                <i class="fas fa-building text-white"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-warning">Información Fiscal</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">NIT</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientNit">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">NCR</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientNcr">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">PASAPORTE</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientPassport">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">ES CONTRIBUYENTE</label>
                                <div class="form-control-plaintext p-2 rounded border" id="clientContribuyente">
                                    <span>-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">ES EXTRANJERO</label>
                                <div class="form-control-plaintext p-2 rounded border" id="clientExtranjero">
                                    <span>-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">ES AGENTE DE RETENCIÓN</label>
                                <div class="form-control-plaintext p-2 rounded border" id="clientAgenteRetencion">
                                    <span>-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info rounded-circle p-2 me-3">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-info">Información Adicional</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">TIPO CONTRIBUYENTE</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientTipoContribuyente">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">ACTIVIDAD ECONÓMICA</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientActividadEconomica">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">REPRESENTANTE LEGAL</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientRepresentanteLegal">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">FECHA DE NACIMIENTO</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <span id="clientBirthday">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
