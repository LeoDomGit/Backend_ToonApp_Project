<?php

use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubFeaturesController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
Route::get('/',[UserController::class,'LoginIndex'])->name('login');
Route::post('/check-login-admin',[UserController::class,'checkLoginAdmin']);
Route::post('/checkLoginAdmin',[UserController::class,'checkLoginAdmin1']);
Route::get('/logout',[UserController::class,'Logout']);
// ================================================================

Route::resource('/roles',RoleController::class);
Route::resource('/users',UserController::class);
Route::resource('/permissions',PermissionsController::class);
Route::post('/permissions/add-role-permision',[PermissionsController::class,'role_permission']);
Route::get('/permissions/roles/{id}',[PermissionsController::class,'get_permissions']);

// ===================================================================
Route::resource('/features',FeaturesController::class);
Route::resource('/sub_feature',SubFeaturesController::class);


Route::post('/checkLoginEmail',[UserController::class,'checkLoginEmailAdmin']);
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
