<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\FeatureImageController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\GroupBackgroundController;
use App\Http\Controllers\ImageSizeController;
use App\Http\Controllers\KeyController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubcriptionPackagesController;
use App\Http\Controllers\SubFeaturesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EffectsController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AiImageCartoonizerController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\SecretKeyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LanguagesController;
use App\Http\Controllers\LanguagesListController;
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

Route::get('/', [UserController::class, 'LoginIndex'])->name('login');
Route::post('/check-login-admin', [UserController::class, 'checkLoginAdmin']);
Route::post('/checkLoginAdmin', [UserController::class, 'checkLoginAdmin1']);

Route::middleware('checkLogin')->group(function () {
    Route::get('/logout', [UserController::class, 'Logout']);
    Route::resource('/roles', RoleController::class);
    Route::resource('/sizes', ImageSizeController::class);
    Route::post('/size-update-image/{id}', [ImageSizeController::class, 'update_image']);
    Route::resource('/users', UserController::class);
    Route::resource('/permissions', PermissionsController::class);
    Route::post('/permissions/add-role-permision', [PermissionsController::class, 'role_permission']);
    Route::get('/permissions/roles/{id}', [PermissionsController::class, 'get_permissions']);
    Route::resource('/features', FeaturesController::class);
    Route::resource('/sub_feature', SubFeaturesController::class);
    Route::resource('/backgrounds', BackgroundController::class);
    Route::get('/sub_features/feature/{feature_id}', [SubFeaturesController::class, 'getSubFeaturesByFeature']);
    Route::post('/feature-update-image/{id}', [FeaturesController::class, 'feature_update_image']);
    Route::post('/save-group-backgrounds', [GroupBackgroundController::class, 'saveGroupBackgrounds']);
    Route::get('/groups/{feature_id}', [GroupBackgroundController::class, 'getGroupsByFeature']);
    Route::post('/remove-group', [GroupBackgroundController::class, 'removeGroup']);
    Route::post('/sub-feature-update-image/{id}', [SubFeaturesController::class, 'sub_feature_update_image']);
    Route::resource('/api_images', FeatureImageController::class);
    Route::post('/api-features-update-image/{id}', [FeatureImageController::class, 'feature_update_image']);
    Route::resource('/keys', KeyController::class);
    Route::post('/updated_size/{id}', [FeaturesController::class, 'updated_size']);
    Route::resource('/packages', SubcriptionPackagesController::class);
    Route::post('packages-update-image/{id}', [SubcriptionPackagesController::class, 'api_update_image']);

    Route::resource('/configs', ConfigController::class);
    Route::resource('/feedback', FeedbackController::class);
    Route::resource('/apivances',  AiImageCartoonizerController::class);
    Route::resource('/secretkeys',  SecretKeyController::class);
    Route::resource('/historys',  ActivityController::class);
    Route::resource('/languages',  LanguagesController::class);
    Route::resource('/language_lists',  LanguagesListController::class);
    //==========================================================
    Route::resource('effects', EffectsController::class);
    Route::post('effects-update-image/{id}', [EffectsController::class, 'api_update_image']);

    Route::post('/group_background', [GroupBackgroundController::class, 'store']);
    Route::put('/group_background/{id}', [GroupBackgroundController::class, 'update']);
    Route::get('/group_background/{id}', [GroupBackgroundController::class, 'show']);
    Route::get('/background/{id}', [GroupBackgroundController::class, 'showBackground']);
    Route::get('/frontground/{id}', [GroupBackgroundController::class, 'showFrontground']);
    Route::delete('/group_background/{id}', [GroupBackgroundController::class, 'destroy']);
    Route::post('/upload_background', [GroupBackgroundController::class, 'uploadBackgroundImages']);
    Route::post('/backgrounds/add-to-group', [BackgroundController::class, 'addImagesToGroup']);
    Route::post('/upload_frontground', [GroupBackgroundController::class, 'uploadFrontGroundImages']);
    Route::post('/assign-to-group', [BackgroundController::class, 'assignToGroup']);



    // Lấy ảnh theo nhóm
    Route::get('/backgrounds/{feature_id}', [BackgroundController::class, 'getImagesByGroup']);
    Route::post('/upload-image', [BackgroundController::class, 'uploadImage']);

    Route::post('/group_background', [GroupBackgroundController::class, 'store']);
    Route::put('/group_background/{id}', [GroupBackgroundController::class, 'update']);
    Route::get('/group_background/{id}', [GroupBackgroundController::class, 'show']);
    Route::delete('/group_background/{id}', [GroupBackgroundController::class, 'destroy']);
});

Route::post('/checkLoginEmail', [UserController::class, 'checkLoginEmailAdmin']);
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
