<?php

namespace App\Providers;

use App\Http\Controllers\PermissionController;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap services.
   *
   * @return void
   */
  public function boot()
  {
    //$verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
    //$permissionController = new PermissionController();
    //$verticalMenuJson = $permissionController->getmenujson();
    //dd($verticalMenuJson);
    //$verticalMenuData = json_decode($verticalMenuJson->original);
    //dd($verticalMenuData);
    //$horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
    //$horizontalMenuData = json_decode($horizontalMenuJson);

    // Share all menuData to all the views
    //\View::share('menuData', [$verticalMenuData, $horizontalMenuData]);
  }
}
