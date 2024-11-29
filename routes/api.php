<?php

use App\Http\Controllers\AiImageCartoonizerController;
use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\ImageAIController;
use App\Http\Controllers\SubcriptionPackagesController;
use App\Http\Controllers\SubFeaturesController;
use App\Http\Controllers\UserController;
use App\Models\Effects;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\HistoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::get('/features', [FeaturesController::class, 'api_index']);
Route::get('/features/{id}', [FeaturesController::class, 'api_detail']);

Route::prefix('customers')->group(function () {
    Route::post('/register', [CustomersController::class, 'register']);
    Route::post('/login', [CustomersController::class, 'login']);
    Route::post('/forget', [CustomersController::class, 'forget_password']);
    Route::post('/social_login', [CustomersController::class, 'social_login']);
});
Route::middleware('device_login')->group(function () {
    Route::put('/customers', action: [CustomersController::class, 'update']);
    Route::get('/logout', [CustomersController::class, 'logout']);
    Route::post('/upload_image', [ImageAIController::class, 'uploadImage']);
    Route::post('/style', [ImageAIController::class, 'cartoon']);
    Route::post('/claymation', [ImageAIController::class, 'claymation']);
    Route::post('/buyPackage', [SubcriptionPackagesController::class, 'buyPackages']);
    // Route::get('/token/{id}', [SubcriptionPackagesController::class, 'getToken']);
    Route::post('/token', [SubcriptionPackagesController::class, 'getToken']);
    Route::post('/profile', [ImageAIController::class, 'changeBackground']);
    Route::get('/effects', [ImageAIController::class, 'getEffect']);
    Route::post('/effect', [ImageAIController::class, 'setup_profile_picture']);
    //=================================================
    Route::get('/features', [FeaturesController::class, 'api_index']);
    Route::get('/features/{id}', [FeaturesController::class, 'api_detail']);
    Route::get('/configs', [ConfigController::class, 'api_index']);
    Route::get('/backgrounds', [BackgroundController::class, 'api_index']);
    Route::get('/backgrounds/{id}', [BackgroundController::class, 'api_single']);
    Route::get('/packages', [SubcriptionPackagesController::class, 'getPackages']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::post('/apivances', [AiImageCartoonizerController::class, 'store']);
    Route::get('/image/{uid}', [ImageController::class, 'getImage']);
    Route::post('/history', [HistoryController::class, 'getCustomerDetails']);
    Route::post('/vancetransform', [ImageController::class, 'uploadImage']);

    // Create a new history record

    // Route::get('/effects', [Effects::class, 'api_index']);
    // Route::get('/effects/{id}', [Effects::class, 'api_single']);
});

Route::get('/download', [ImageController::class, 'download']);

Route::get('/configs', [ConfigController::class, 'api_index']);


Route::get('/backgrounds', [BackgroundController::class, 'api_index']);
Route::get('/packages', [SubcriptionPackagesController::class, 'getPackages']);

Route::get('/update_feature_slug', [FeaturesController::class, 'update_feature_slug']);
Route::get('/update_sub_feature_slug', [SubFeaturesController::class, 'update_feature_slug']);

Route::resource('test', TestController::class);
Route::prefix('users')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'Login']);
    Route::post('/register-with-email', [UserController::class, 'RegisterWithEmail']);
    Route::post('/login-with-email', [UserController::class, 'LoginWithEmail']);
});

Route::post('/upload-zip', [BackgroundController::class, 'uploadAndUnzip']);
