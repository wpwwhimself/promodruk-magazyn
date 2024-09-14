<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect("/", "admin/dashboard");

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::get("/login", "input")->name("login");
    Route::post("/login", "authenticate")->name("authenticate");
    Route::post("/change-password", "changePassword")->name("change-password");
    Route::middleware("auth")->get("/logout", "logout")->name("logout");
});

Route::middleware("auth")->controller(AdminController::class)->prefix("admin")->group(function () {
    foreach(AdminController::$pages as [$label, $route]) {
        Route::get(Str::slug($route), Str::camel($route))->name(Str::kebab($route));

        if ($route !== "dashboard") {
            Route::get($route."/edit/{id?}", Str::singular(Str::camel($route))."Edit")->name("$route-edit");
        }
    }
    Route::get("main-attributes/edit/{id?}", "mainAttributeEdit")->name("main-attributes-edit");
    Route::get("main-attributes/prune", "mainAttributePrune")->name("main-attributes-prune");

    Route::prefix("products/import")->group(function () {
        Route::get("/", "productImport")->name("products-import");
        Route::post("/", "productImportFetch")->name("products-import-fetch");
        Route::post("/choose", "productImportChoose")->name("products-import-choose");
    });

    Route::prefix("synchronizations")->group(function () {
        Route::get("enable/{supplier_name}/{mode}/{enabled}", "synchEnable")->name("synch-enable");
        Route::get("reset/{supplier_name?}", "synchReset")->name("synch-reset");
    });

    Route::prefix("settings/update")->group(function () {
        foreach(AdminController::$updaters as $slug) {
            Route::post(Str::slug($slug), Str::camel("update-".$slug))->name(Str::kebab("update-".$slug));
        }
    });

});

Route::prefix("test")->group(function () {
    Route::get("anda/{itemNumber}", function ($itemNumber) {
        (new \App\DataIntegrators\AndaHandler)->test($itemNumber);
    });
});
