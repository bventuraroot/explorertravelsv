<div class="alert alert-warning" role="alert">
    <h6 class="alert-heading">¡Advertencia!</h6>
    <p class="mb-0">
        Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente.
        Antes de eliminar tu cuenta, por favor descarga cualquier dato o información que desees conservar.
    </p>
</div>

<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
    Eliminar Cuenta
</button>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmUserDeletionLabel">¿Estás seguro de que quieres eliminar tu cuenta?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-body">
                    <p class="text-muted">
                        Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán eliminados permanentemente.
                        Por favor ingresa tu contraseña para confirmar que deseas eliminar permanentemente tu cuenta.
                    </p>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>
