<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.users.permissions.index');
    }

    public function getpermission()
    {
        $permission['data'] = Permission::all();
        //dd($permission['data']);
        foreach($permission['data'] as $index => $value){
                @$permission['data'][$index]['roles']=[];
                $idpermissiondata = @$value->id;
                $roles = "SELECT b.name AS rolename FROM role_has_permissions a
                INNER JOIN roles b ON a.role_id=b.id
                WHERE a.permission_id='$idpermissiondata'
                GROUP BY b.name";
                $result = DB::select(DB::raw($roles));
                foreach($result as $index2 => $value2){
                    @$permission['data'][$index]['roles']=$result;
                }
        }
        //dd($permission['data']);
        return response()->json($permission);
    }

    public function getmenujson(){
        $userId=auth()->user()->id;
        $rolhasuser = "SELECT
                a.model_id UserID,
                b.id Rolid,
                b.name Rol,
                d.id PermisoID,
                d.name Permiso
                FROM model_has_roles a
                INNER JOIN roles b ON a.role_id=b.id
                LEFT JOIN role_has_permissions c ON b.id=c.role_id
                LEFT JOIN permissions d ON c.permission_id=d.id
                WHERE model_id=$userId";
        $result = DB::select(DB::raw($rolhasuser));
        //dd($result);

        $menu = [
            [
                "url" => "/",
                "name" => "Home",
                "icon" => "menu-icon fa-solid fa-home",
                "slug" => "dashboard"
            ],
            [
                "name" => "Administracion",
                "icon" => "menu-icon fa-solid fa-shield",
                "slug" => "user.index",
                "badge" => ["primary", "3"],
                "submenu" => [
                    [
                        "url" => "/user/index",
                        "name" => "Usuarios",
                        "slug" => "user.index"
                    ],
                    [
                        "url" => "/rol/index",
                        "name" => "Roles",
                        "slug" => "user.rol.index"
                    ],
                    [
                        "url" => "/permission/index",
                        "name" => "Permisos",
                        "slug" => "user.permission.index"
                    ],
                    [
                        "url" => "/backups",
                        "name" => "Respaldos de BD",
                        "slug" => "backups.index"
                    ]
                ]
            ],
            [
                "url" => "/company/index",
                "name" => "Empresas",
                "icon" => "menu-icon fa-solid fa-id-badge",
                "slug" => "company.index"
            ],
            [
                "url" => "/client/index",
                "name" => "Clientes",
                "icon" => "menu-icon fa-solid fa-user-plus",
                "slug" => "client.index"
            ],
            [
                "name" => "Produccion",
                "icon" => "menu-icon fa-solid fa-feed",
                "slug" => "user.index",
                "badge" => ["primary", "2"],
                "submenu" => [
                    [
                        "url" => "/product/index",
                        "name" => "Productos",
                        "slug" => "product.index"
                    ],
                    [
                        "url" => "/provider/index",
                        "name" => "Proveedores",
                        "slug" => "provider.index"
                    ]
                ]
            ],
            [
                "url" => "/sale/index",
                "name" => "Ventas",
                "icon" => "menu-icon fa-solid fa-dollar",
                "slug" => "sale.index"
            ],
            [
                "url" => "/purchase/index",
                "name" => "Compras",
                "icon" => "menu-icon fa-solid fa-truck",
                "slug" => "purchase.index"
            ],
            [
                "url" => "/credit/index",
                "name" => "Creditos",
                "icon" => "menu-icon fa-solid fa-credit-card",
                "slug" => "credit.index"
            ],
            [
                "url" => "/report/index",
                "name" => "Reportes",
                "icon" => "menu-icon fa-solid fa-line-chart",
                "slug" => "report.index",
                "badge" => ["primary", "7"],
                "submenu" => [
                    [
                        "url" => "/report/sales",
                        "name" => "Ventas",
                        "slug" => "report.sales"
                    ],
                    [
                        "url" => "/report/contribuyentes",
                        "name" => "Ventas Contribuyentes",
                        "slug" => "report.contribuyentes"
                    ],
                    [
                        "url" => "/report/directas",
                        "name" => "Ventas Directas",
                        "slug" => "report.directas"
                    ],
                    [
                        "url" => "/report/consumidor",
                        "name" => "Ventas Consumidor",
                        "slug" => "report.consumidor"
                    ],
                    [
                        "url" => "/report/purchases",
                        "name" => "Compras",
                        "slug" => "report.purchases"
                    ],
                    [
                        "url" => "/report/bookpurchases",
                        "name" => "Libro de Compras",
                        "slug" => "report.bookpurchases"
                    ],
                    [
                        "url" => "/report/reportyear",
                        "name" => "Ventas y compras por año",
                        "slug" => "report.reportyear"
                    ],
                    [
                        "url" => "/report/ivacontrol",
                        "name" => "Control de IVA y Pago a Cuenta",
                        "slug" => "report.ivacontrol"
                    ]
                ]
            ],
            [
                "url" => "/manuals",
                "name" => "Manuales",
                "icon" => "menu-icon fa-solid fa-book",
                "slug" => "manuals.index"
            ],
            [
                "name" => "Administración DTE",
                "icon" => "menu-icon fa-solid fa-cogs",
                "slug" => "dte.dashboard",
                "badge" => ["primary", "8"],
                "submenu" => [
                    [
                        "url" => "/correlativos",
                        "name" => "Correlativos",
                        "icon" => "menu-icon fa-solid fa-list-ol",
                        "slug" => "correlativos.index"
                    ],
                    [
                        "url" => "/firmador/test",
                        "name" => "Prueba de Conectividad Firmador",
                        "icon" => "menu-icon fa-solid fa-network-wired",
                        "slug" => "firmador.test"
                    ],
                    [
                        "url" => "/dte-dashboard/dashboard",
                        "name" => "Dashboard DTE",
                        "icon" => "menu-icon fa-solid fa-chart-line",
                        "slug" => "dte.dashboard"
                    ],
                    [
                        "url" => "/dte-admin/errores",
                        "name" => "Gestión de Errores",
                        "icon" => "menu-icon fa-solid fa-exclamation-triangle",
                        "slug" => "dte.dashboard"
                    ],
                    [
                        "url" => "/dte-admin/contingencias",
                        "name" => "Gestión de Contingencias",
                        "icon" => "menu-icon fa-solid fa-shield-alt",
                        "slug" => "dte.dashboard"
                    ],
                    [
                        "url" => "/factmh/contingencias/dashboard",
                        "name" => "Dashboard Contingencias",
                        "icon" => "menu-icon fa-solid fa-chart-pie",
                        "slug" => "factmh.contingencias.dashboard"
                    ],
                    [
                        "url" => "/factmh/contingencias",
                        "name" => "Contingencias MH",
                        "icon" => "menu-icon fa-solid fa-satellite-dish",
                        "slug" => "factmh.contingencias"
                    ],
                    [
                        "url" => "/config/index",
                        "name" => "Configuraciones Ambiente",
                        "icon" => "menu-icon fa-solid fa-cog",
                        "slug" => "config.index"
                    ]
                ]
            ]
        ];

        $filteredMenu = [];
        //dd($result);
        if(@$result[0]->Rolid!=1){

        // Agregar el primer elemento "Home" directamente
        if (isset($menu[0])) {
            $filteredMenu[] = $menu[0];
        }

        foreach ($menu as $index => $menuItem) {
            // Saltar el primer elemento (ya agregado)
            if ($index === 0) {
                continue;
            }

            // Verifica si el usuario tiene acceso a la sección principal (slug)
            if (in_array($menuItem['slug'], array_column($result, 'Permiso'))) {
                // Clona el menú principal
                $filteredItem = $menuItem;

                // Filtra el submenu si existe
                if (isset($menuItem['submenu'])) {
                    $filteredSubmenu = [];
                    foreach ($menuItem['submenu'] as $submenuItem) {
                        // Verifica el permiso para cada subelemento del menú
                        if (in_array($submenuItem['slug'], array_column($result, 'Permiso'))) {
                            $filteredSubmenu[] = $submenuItem;
                        }
                    }
                    // Solo añade el submenu si hay permisos
                    if (!empty($filteredSubmenu)) {
                        $filteredItem['submenu'] = $filteredSubmenu;
                    }
                }

                $filteredMenu[] = $filteredItem;
            }
        }

        // Encerrar todo en la palabra "menu"
$finalMenu = ['menu' => $filteredMenu];
$menuJson = json_encode($finalMenu);
//dd($menuJson);
}else {
    $finalMenu = ['menu' => $menu];
    $menuJson = json_encode($finalMenu);;
}
return response()->json($menuJson);
    }

    public function getpermissionjson(){
        $userId=auth()->user()->id;
        $rolhasuser = "SELECT
                a.model_id UserID,
                b.id Rolid,
                b.name Rol,
                d.id PermisoID,
                d.name Permiso
                FROM model_has_roles a
                INNER JOIN roles b ON a.role_id=b.id
                LEFT JOIN role_has_permissions c ON b.id=c.role_id
                LEFT JOIN permissions d ON c.permission_id=d.id
                WHERE model_id=$userId";
        $result = DB::select(DB::raw($rolhasuser));
        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Permission::create(['name' => $request->modalPermissionName]);
        return redirect()->route('permission.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $permission = Permission::find($request->editPermissionid);
        $permission->name = $request->editPermissionName;
        $permission->save();
        return redirect()->route('permission.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $permission = Permission::find(base64_decode($id));
        $permission->delete();
        return response()->json(array(
            "res" => "1"
        ));
    }
}
