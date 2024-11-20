<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PermissionController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Verifica que el usuario esté autenticado
        if (!$user) {
            abort(403, 'No tienes permiso para acceder a esta ruta.');
        }

        // Llama a PermissionController para obtener el JSON de permisos
        $permissionController = new PermissionController();
        $verticalMenuJson = $permissionController->getpermissionjson();
        //$permisos = array_column($array, 'Permiso');
        foreach($verticalMenuJson->original as $permiso){
            $rolvalue = $permiso->Rolid;
            $permisoValue = $permiso->Permiso;
        }
        // Extrae el permiso relacionado con la ruta actual (esto depende de cómo estructures el menú y las rutas)
        $requestedPermission = $request->route()->getName();

        // Verifica si el usuario tiene el permiso relacionado con la ruta actual
        if ($permisoValue===$requestedPermission || $rolvalue==1) {

        }else{
            // Si no tiene permiso, aborta con error 403
            abort(403, 'No tienes permiso para acceder a esta ruta.');
        }

        return $next($request);
    }
}
