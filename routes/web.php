<?php

use App\Http\Controllers\EconomicactivityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContingenciasController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionElectronicaController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CorrelativoController;
use App\Http\Controllers\DteAdminController;
use App\Http\Controllers\FirmadorTestController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\DteDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

//Route::get('/dashboard', function () { return view('dashboard');})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'home'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Rutas del perfil - accesibles para todos los usuarios autenticados
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['auth', 'permission:manage_users'])->group(function () {


Route::group(['prefix' => 'client', 'as' => 'client.'], function(){

    Route::get('index/{company?}', [ClientController::class, 'index'])->name('index');
    Route::get('getclientbycompany/{company}', [ClientController::class, 'getclientbycompany'])->name('getclientbycompany');
    Route::get('view/{client}', [CompanyController::class, 'show'])->name('view');
    Route::get('edit/{client}', [ClientController::class, 'edit'])->name('edit');
    Route::get('getClientid/{client}', [ClientController::class, 'getClientid'])->name('getClientid');
    Route::get('keyclient/{num}/{tpersona}', [ClientController::class, 'keyclient'])->name('keyclient');
    Route::get('gettypecontri/{client}', [ClientController::class, 'gettypecontri'])->name('gettypecontri');
    Route::patch('update', [ClientController::class, 'update'])->name('update');
    Route::get('create', [ClientController::class, 'create'])->name('create');
    Route::post('store', [ClientController::class, 'store'])->name('store');
    Route::get('destroy/{client}', [ClientController::class, 'destroy'])->name('destroy');

    });

Route::group(['prefix' => 'company', 'as' => 'company.'], function(){

    Route::get('index', [CompanyController::class, 'index'])->name('index');
    Route::get('view/{company}', [CompanyController::class, 'show'])->name('view');
    Route::get('getCompany', [CompanyController::class, 'getCompany'])->name('getCompany');
    Route::get('getCompanybyuser/{iduser}', [CompanyController::class, 'getCompanybyuser'])->name('getCompanybyuser');
    Route::get('gettypecontri/{company}', [CompanyController::class, 'gettypecontri'])->name('gettypecontri');
    Route::get('getCompanytag', [CompanyController::class, 'getCompanytag'])->name('getCompanytag');
    Route::get('getCompanyid/{company}', [CompanyController::class, 'getCompanyid'])->name('getCompanyid');
    Route::post('store', [CompanyController::class, 'store'])->name('store');
    Route::patch('update', [CompanyController::class, 'update'])->name('update');
    Route::get('destroy/{company}', [CompanyController::class, 'destroy'])->name('destroy');

    });

    Route::get('getcountry', [CountryController::class, 'getcountry'])->name('getcountry');
    Route::get('getdepartment/{pais}', [DepartmentController::class, 'getDepartment'])->name('getDepartment');
    Route::get('getmunicipality/{dep}', [MunicipalityController::class, 'getMunicipality'])->name('getmunicipios');
    Route::get('geteconomicactivity/{pais}', [EconomicactivityController::class, 'geteconomicactivity'])->name('geteconomicactivity');
    Route::get('getroles', [RolController::class, 'getRoles'])->name('getroles');

Route::group(['prefix' => 'user', 'as' => 'user.'], function(){
    Route::get('index', [UserController::class, 'index'])->name('index');
    Route::get('getusers', [UserController::class, 'getusers'])->name('getusers');
    Route::get('getuserid/{user}', [UserController::class, 'getuserid'])->name('getuserid');
    Route::get('valmail/{mail}', [UserController::class, 'valmail'])->name('valmail');
    Route::post('store', [UserController::class, 'store'])->name('store');
    Route::patch('update', [UserController::class, 'update'])->name('update');
    Route::get('changedtatus/{user}/status/{status}', [UserController::class, 'changedtatus'])->name('changedtatus');
    Route::get('destroy/{user}', [UserController::class, 'destroy'])->name('destroy');

    });

Route::group(['prefix' => 'rol', 'as' => 'rol.'], function(){
    Route::get('index', [RolController::class, 'index'])->name('index');
    Route::patch('update', [RolController::class, 'update'])->name('update');
    Route::post('store', [RolController::class, 'store'])->name('store');

    });

Route::group(['prefix' => 'permission', 'as' => 'permission.'], function(){
    Route::get('index', [PermissionController::class, 'index'])->name('index');
    Route::patch('update', [PermissionController::class, 'update'])->name('update');
    Route::post('store', [PermissionController::class, 'store'])->name('store');
    Route::get('destroy/{id}', [PermissionController::class, 'destroy'])->name('destroy');
    Route::get('getpermission', [PermissionController::class, 'getpermission'])->name('getpermission');
    Route::get('getmenujson', [PermissionController::class, 'getmenujson'])->name('getmenujson');

    });

Route::group(['prefix' => 'provider', 'as' => 'provider.'], function(){
        Route::get('index', [ProviderController::class, 'index'])->name('index');
        Route::get('getproviders', [ProviderController::class, 'getproviders'])->name('getproviders');
        Route::get('getproviderid/{id}', [ProviderController::class, 'getproviderid'])->name('getproviderid');
        Route::patch('update', [ProviderController::class, 'update'])->name('update');
        Route::post('store', [ProviderController::class, 'store'])->name('store');
        Route::get('destroy/{id}', [ProviderController::class, 'destroy'])->name('destroy');
        Route::get('getpermission', [ProviderController::class, 'getpermission'])->name('getpermission');

    });

Route::group(['prefix' => 'product', 'as' => 'product.'], function(){
        Route::get('index', [ProductController::class, 'index'])->name('index');
        Route::get('getproductid/{id}', [ProductController::class, 'getproductid'])->name('getproductid');
        Route::get('getproductall', [ProductController::class, 'getproductall'])->name('getproductall');
        Route::patch('update', [ProductController::class, 'update'])->name('update');
        Route::post('store', [ProductController::class, 'store'])->name('store');
        Route::get('destroy/{id}', [ProductController::class, 'destroy'])->name('destroy');
        Route::get('getpermission', [ProductController::class, 'getpermission'])->name('getpermission');

    });

Route::group(['prefix' => 'sale', 'as' => 'sale.'], function(){
        Route::get('index', [SaleController::class, 'index'])->name('index');
        Route::get('create', [SaleController::class, 'create'])->name('create');
        Route::get('getproductid/{id}', [SaleController::class, 'getproductid'])->name('getproductid');
        Route::get('getproductbyid/{id}', [SaleController::class, 'getproductbyid'])->name('getproductbyid');
        Route::get('getdatadocbycorr/{corr}', [SaleController::class, 'getdatadocbycorr'])->name('getdatadocbycorr');
        Route::get('getdatadocbycorr2/{corr}', [SaleController::class, 'getdatadocbycorr2'])->name('getdatadocbycorr2');
        Route::patch('update', [SaleController::class, 'update'])->name('update');
        Route::post('store', [SaleController::class, 'store'])->name('store');
        Route::get('createdocument/{corr}/{amount}', [SaleController::class, 'createdocument'])->name('createdocument');
        Route::get('impdoc/{corr}', [SaleController::class, 'impdoc'])->name('impdoc');
        Route::get('destroy/{id}', [SaleController::class, 'destroy'])->name('destroy');
        Route::get('savefactemp/{idsale}/{clientid}/{productid}/{cantida}/{price}/{nosujeto}/{exento}/{gravado}/{iva}/{renta}/{retenido}/{acuenta}/{fpago}/{fee}/{reserva}/{ruta}/{destino}/{linea}/{canal}', [SaleController::class, 'savefactemp'])->name('savefactemp');
        Route::get('newcorrsale/{idempresa}/{iduser}/{typedocument}', [SaleController::class, 'newcorrsale'])->name('newcorrsale');
        Route::get('getdetailsdoc/{corr}', [SaleController::class, 'getdetailsdoc'])->name('getdetailsdoc');
        Route::get('destroysaledetail/{idsaledetail}', [SaleController::class, 'destroysaledetail'])->name('destroysaledetail');
        Route::get('ncr/{id_sale}', [SaleController::class, 'ncr'])->name('ncr');
        Route::get('envia_correo', [SaleController::class, 'envia_correo'])->name('envia_correo');
        Route::get('sale/print/{id}', [SaleController::class, 'print'])->name('print');
        Route::get('destinos', [SaleController::class, 'destinos'])->name('destinos');
        Route::get('linea', [SaleController::class, 'linea'])->name('linea');

    });

Route::group(['prefix' => 'purchase', 'as' => 'purchase.'], function(){
        Route::get('index', [PurchaseController::class, 'index'])->name('index');
        Route::post('store', [PurchaseController::class, 'store'])->name('store');
        Route::patch('update', [PurchaseController::class, 'update'])->name('update');
        Route::get('getpurchaseid/{id}', [PurchaseController::class, 'getpurchaseid'])->name('getpurchaseid');
        Route::get('destroy/{id}', [PurchaseController::class, 'destroy'])->name('destroy');
    });


    Route::group(['prefix' => 'credit', 'as' => 'credit.'], function(){
        Route::get('index', [CreditController::class, 'index'])->name('index');
        Route::post('store', [CreditController::class, 'store'])->name('store');
        Route::patch('update', [CreditController::class, 'update'])->name('update');
        Route::patch('addpay', [CreditController::class, 'addpay'])->name('addpay');
        Route::get('getinfocredit/{id}', [CreditController::class, 'getinfocredit'])->name('getinfocredit');
        Route::get('destroy/{id}', [CreditController::class, 'destroy'])->name('destroy');
    });

Route::group(['prefix' => 'report', 'as' => 'report.'], function(){
        Route::get('sales', [ReportsController::class, 'sales'])->name('sales');
        Route::get('purchases', [ReportsController::class, 'purchases'])->name('purchases');
        Route::get('reportsales/{company}/{year}/{period}', [ReportsController::class, 'reportsales'])->name('reportsales');
        Route::get('reportpurchases/{company}/{year}/{period}', [ReportsController::class, 'reportpurchases'])->name('reportpurchases');
        Route::get('contribuyentes', [ReportsController::class, 'contribuyentes'])->name('contribuyentes');
        Route::get('reportyear', [ReportsController::class, 'reportyear'])->name('reportyear');
        Route::post('yearsearch', [ReportsController::class, 'yearsearch'])->name('yearsearch');
        Route::post('contribusearch', [ReportsController::class, 'contribusearch'])->name('contribusearch');
        Route::get('directas', [ReportsController::class, 'directas'])->name('directas');
        Route::get('consumidor', [ReportsController::class, 'consumidor'])->name('consumidor');
        Route::post('consumidorsearch', [ReportsController::class, 'consumidorsearch'])->name('consumidorsearch');
        Route::get('bookpurchases', [ReportsController::class, 'bookpurchases'])->name('bookpurchases');
        Route::post('comprassearch', [ReportsController::class, 'comprassearch'])->name('comprassearch');
    });

Route::group(['prefix' => 'factmh', 'as' => 'factmh.'], function(){

        Route::get('mostrar_cola', [FacturacionElectronicaController::class, 'mostrar_cola'])->name('show_queue');
        Route::get('procesa_cola', [FacturacionElectronicaController::class, 'procesa_cola'])->name('run_queue');
        Route::get('muestra_enviados', [FacturacionElectronicaController::class, 'muestra_enviados'])->name('show_sends');
        Route::get('muestra_rechazados', [FacturacionElectronicaController::class, 'muestra_rechazados'])->name('show_rejected');
        Route::get('prueba_certificado', [FacturacionElectronicaController::class, 'prueba_certificado'])->name('test_crt');
});

Route::group(['prefix' => 'config', 'as' => 'config.'], function(){

    Route::get('index', [ConfigController::class, 'index'])->name('index');
    Route::post('store', [ConfigController::class, 'store'])->name('store');
    Route::post('update', [ConfigController::class, 'update'])->name('update');
    Route::get('getconfigid/{id}', [ConfigController::class, 'getconfigid'])->name('getconfigid');
    Route::get('destroy/{id}', [ConfigController::class, 'destroy'])->name('destroy');
});

Route::group(['prefix' => 'factmh', 'as' => 'factmh.'], function(){

    Route::get('contingencias', [ContingenciasController::class, 'contingencias'])->name('contingencias');
    Route::post('store', [ContingenciasController::class, 'store'])->name('store');
    Route::get('autoriza_contingencia/{empresa}/{id}', [ContingenciasController::class, 'autoriza_contingencia'])->name('autoriza_contingencia');
    Route::get('procesa_contingencia/{id}', [ContingenciasController::class, 'procesa_contingencia'])->name('procesa_contingencia');
    Route::get('muestra_lote/{id}', [ContingenciasController::class, 'muestra_lote'])->name('muestra_lote');
    Route::get('update', [ConfigController::class, 'update'])->name('update');
    Route::get('getconfigid/{id}', [ConfigController::class, 'getconfigid'])->name('getconfigid');
    Route::get('destroy/{id}', [ConfigController::class, 'destroy'])->name('destroy');
});

// Gestión de correlativos y APIs
Route::middleware('auth')->group(function () {
    Route::group(['prefix' => 'correlativos', 'as' => 'correlativos.'], function(){
        Route::get('/', [CorrelativoController::class, 'index'])->name('index');
        Route::get('create', [CorrelativoController::class, 'create'])->name('create');
        Route::post('/', [CorrelativoController::class, 'store'])->name('store');
        Route::get('{id}', [CorrelativoController::class, 'show'])->name('show');
        Route::get('{id}/edit', [CorrelativoController::class, 'edit'])->name('edit');
        Route::put('{id}', [CorrelativoController::class, 'update'])->name('update');
        Route::delete('{id}', [CorrelativoController::class, 'destroy'])->name('destroy');
        Route::get('estadisticas/view', [CorrelativoController::class, 'estadisticas'])->name('estadisticas');
        Route::post('{id}/reactivar', [CorrelativoController::class, 'reactivar'])->name('reactivar');
        Route::patch('{id}/estado', [CorrelativoController::class, 'cambiarEstado'])->name('cambiar-estado');
        Route::get('por-empresa/ajax', [CorrelativoController::class, 'porEmpresa'])->name('por-empresa');
    });

    Route::group(['prefix' => 'api/correlativos', 'as' => 'correlativos.api.'], function(){
        Route::post('siguiente-numero', [CorrelativoController::class, 'apiSiguienteNumero'])->name('siguiente-numero');
        Route::post('validar-disponibilidad', [CorrelativoController::class, 'apiValidarDisponibilidad'])->name('validar-disponibilidad');
        Route::get('estadisticas', [CorrelativoController::class, 'apiEstadisticas'])->name('estadisticas');
        Route::get('por-empresa', [CorrelativoController::class, 'porEmpresa'])->name('por-empresa-api');
    });


    // Prueba de firmador (similar a RomaCopies)
    Route::group(['prefix' => 'firmador', 'as' => 'firmador.'], function(){
        Route::get('test', [FirmadorTestController::class, 'index'])->name('test');
        Route::post('test-connection', [FirmadorTestController::class, 'testConnection'])->name('test-connection');
        Route::post('test-firma', [FirmadorTestController::class, 'testFirma'])->name('test-firma');
        Route::get('server-info', [FirmadorTestController::class, 'serverInfo'])->name('server-info');
    });

    // Manuales de Usuario
    Route::group(['prefix' => 'manuals', 'as' => 'manuals.'], function(){
        Route::get('/', [ManualController::class, 'index'])->name('index');
        Route::get('create', [ManualController::class, 'create'])->name('create');
        Route::post('/', [ManualController::class, 'store'])->name('store');
        Route::get('{id}', [ManualController::class, 'show'])->name('show');
        Route::get('{id}/edit', [ManualController::class, 'edit'])->name('edit');
        Route::put('{id}', [ManualController::class, 'update'])->name('update');
        Route::delete('{id}', [ManualController::class, 'destroy'])->name('destroy');
        Route::get('modulo/{modulo}', [ManualController::class, 'porModulo'])->name('por-modulo');
    });

    // Dashboard DTE
    Route::group(['prefix' => 'dte-dashboard', 'as' => 'dte-dashboard.'], function(){
        Route::get('dashboard', [DteDashboardController::class, 'index'])->name('dashboard');
        Route::post('procesar-cola', [DteDashboardController::class, 'procesarCola'])->name('procesar-cola');
        Route::post('procesar-reintentos', [DteDashboardController::class, 'procesarReintentos'])->name('procesar-reintentos');
        Route::get('estadisticas-tiempo-real', [DteDashboardController::class, 'estadisticasTiempoReal'])->name('estadisticas-tiempo-real');
        Route::get('show/{id}', [DteDashboardController::class, 'show'])->name('show'); 
    });

    // Administración DTE
    Route::group(['prefix' => 'dte-admin', 'as' => 'dte.'], function(){
        // Dashboard y estadísticas
        Route::get('dashboard', [DteAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('estadisticas', [DteAdminController::class, 'estadisticas'])->name('estadisticas');
        Route::get('estadisticas-tiempo-real', [DteAdminController::class, 'estadisticasTiempoReal'])->name('estadisticas-tiempo-real');

        // Procesamiento
        Route::post('procesar-cola', [DteAdminController::class, 'procesarCola'])->name('procesar-cola');
        Route::post('procesar-reintentos', [DteAdminController::class, 'procesarReintentos'])->name('procesar-reintentos');
        Route::post('reintentar/{dteId}', [DteAdminController::class, 'reintentarDte'])->name('reintentar-dte');

        // Gestión de errores
        Route::get('errores', [DteAdminController::class, 'errores'])->name('errores');
        Route::post('errores/{errorId}/resolver', [DteAdminController::class, 'resolverError'])->name('resolver-error');
        Route::get('error-show/{id}', [DteAdminController::class, 'showError'])->name('error-show');
        Route::get('dte-show/{id}', [DteAdminController::class, 'showDte'])->name('show');

        // Gestión de contingencias
        Route::get('contingencias', [DteAdminController::class, 'contingencias'])->name('contingencias');
        Route::post('contingencias', [DteAdminController::class, 'crearContingencia'])->name('crear-contingencia');
        Route::post('contingencias/{id}/aprobar', [DteAdminController::class, 'aprobarContingencia'])->name('aprobar-contingencia');
        Route::post('contingencias/{id}/activar', [DteAdminController::class, 'activarContingencia'])->name('activar-contingencia');


        // API para contingencias
        Route::get('dtes-para-contingencia', [DteAdminController::class, 'getDtesParaContingencia'])->name('dtes-para-contingencia');
    });

    // Rutas para prueba del firmador
    Route::group(['prefix' => 'firmador', 'as' => 'firmador.'], function(){
        Route::get('test', [FirmadorTestController::class, 'index'])->name('test');
        Route::post('test-connection', [FirmadorTestController::class, 'testConnection'])->name('test-connection');
        Route::post('test-firma', [FirmadorTestController::class, 'testFirma'])->name('test-firma');
        Route::get('test-network', [FirmadorTestController::class, 'testNetwork'])->name('test-network');
        Route::get('server-info', [FirmadorTestController::class, 'serverInfo'])->name('server-info');
        Route::get('ambientes', [FirmadorTestController::class, 'getAmbientes'])->name('ambientes');
    });
});
});
});



require __DIR__.'/auth.php';
