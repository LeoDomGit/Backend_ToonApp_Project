<?php

use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\ImageAIController;
use App\Http\Controllers\SubcriptionPackagesController;
use App\Http\Controllers\SubFeaturesController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomersController;

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


Route::prefix('customers')->group(function () {
    Route::post('/register', [CustomersController::class, 'register']);
    Route::post('/login', [CustomersController::class, 'login']);
    Route::post('/forget', [CustomersController::class, 'forget_password']);
    Route::post('/social_login', [CustomersController::class, 'social_login']);
});
Route::middleware('device_login')->group(function () {
    Route::put('/customers', [CustomersController::class, 'update']);
    Route::get('/logout',[CustomersController::class, 'logout']);
    Route::post('/upload_image',[ImageAIController::class,'uploadImage']);
    Route::post('/style',[ImageAIController::class,'cartoon']);
    Route::post('/claymation',[ImageAIController::class,'claymation']);
    Route::post('/buyPackage',[SubcriptionPackagesController::class,'buyPackages']);
    Route::get('/token/{id}',[SubcriptionPackagesController::class,'getToken']);
    Route::post('/profile',[ImageAIController::class,'changeBackground']);
    Route::get('/effects',[ImageAIController::class,'getEffect']);
    Route::post('/effect',[ImageAIController::class,'setup_profile_picture']);
//=================================================
    Route::get('/features',[FeaturesController::class,'api_index']);
    Route::get('/features/{id}',[FeaturesController::class,'api_detail']);
    Route::get('/configs',[ConfigController::class,'api_index']);
    Route::get('/backgrounds',[BackgroundController::class,'api_index']);
    Route::get('/packages',[SubcriptionPackagesController::class,'getPackages']);
});





Route::get('/update_feature_slug',[FeaturesController::class,'update_feature_slug']);
Route::get('/update_sub_feature_slug',[SubFeaturesController::class,'update_feature_slug']);

Route::middleware('auth:sanctum')->get('/test', function() {
    return response()->json(Auth::user());
});
Route::prefix('users')->group(function () {
    Route::post('/register',[UserController::class,'register']);
    Route::post('/login',[UserController::class,'Login']);
    Route::post('/register-with-email',[UserController::class,'RegisterWithEmail']);
    Route::post('/login-with-email',[UserController::class,'LoginWithEmail']);
});

Route::post('/upload-zip', [BackgroundController::class, 'uploadAndUnzip']);
