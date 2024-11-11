<?php

use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\FeatureImageController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\ImageSizeController;
use App\Http\Controllers\KeyController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubcriptionPackagesController;
use App\Http\Controllers\SubFeaturesController;
use App\Http\Controllers\UserController;
use App\Models\SubcriptionPackage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SubscriptionPackageController;
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

Route::get('/', [UserController::class, 'LoginIndex'])->name('login');
Route::post('/check-login-admin', [UserController::class, 'checkLoginAdmin']);
Route::post('/checkLoginAdmin', [UserController::class, 'checkLoginAdmin1']);

Route::middleware('checkLogin')->group(function () {
    Route::get('/logout', [UserController::class, 'Logout']);
    Route::resource('/roles', RoleController::class);
    Route::resource('/sizes', ImageSizeController::class);
    Route::resource('/users', UserController::class);
    Route::resource('/permissions', PermissionsController::class);
    Route::post('/permissions/add-role-permision', [PermissionsController::class, 'role_permission']);
    Route::get('/permissions/roles/{id}', [PermissionsController::class, 'get_permissions']);
    Route::resource('/features', FeaturesController::class);
    Route::resource('/sub_feature', SubFeaturesController::class);
    Route::resource('/backgrounds', BackgroundController::class);
    Route::post('/feature-update-image/{id}', [FeaturesController::class, 'feature_update_image']);
    Route::post('/sub-feature-update-image/{id}', [SubFeaturesController::class, 'sub_feature_update_image']);
    Route::resource('/api_images', FeatureImageController::class);
    Route::post('/api-features-update-image/{id}', [FeatureImageController::class, 'feature_update_image']);
    Route::resource('/keys', KeyController::class);
    Route::post('/updated_size/{id}', [FeaturesController::class, 'updated_size']);
    Route::resource('/packages', controller: SubcriptionPackagesController::class);
});

Route::post('/checkLoginEmail', [UserController::class, 'checkLoginEmailAdmin']);
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
