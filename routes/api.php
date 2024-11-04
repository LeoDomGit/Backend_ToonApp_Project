<?php

use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\ImageAIController;
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
Route::get('/features',[FeaturesController::class,'api_index']);
Route::get('/features/{id}',[FeaturesController::class,'api_detail']);

Route::prefix('customers')->group(function () {
    Route::post('/register', [CustomersController::class, 'register']);
    Route::post('/login', [CustomersController::class, 'login']);
    Route::post('/forget', [CustomersController::class, 'forget_password']);
    Route::post('/social_login', [CustomersController::class, 'social_login']);
   
});
Route::middleware('device_login')->group(function () {
    Route::put('/customers', [CustomersController::class, 'update']);
    Route::get('/logout',[CustomersController::class, 'logout']);
    Route::prefix('image_ai')->group(function () {
        Route::post('/change_background',[ImageAIController::class,'changeBackground']);
        Route::post('/cartoon_style',[ImageAIController::class,'cartoonStyle']);
        Route::post('/remove_background',[ImageAIController::class,'changeBackground']);
        Route::post('/claymation',[ImageAIController::class,'claymation']);
        Route::post('/disney_charactors',[ImageAIController::class,'disneyCharators']);
        Route::post('/fullbody_cartoon',[ImageAIController::class,'fullBodyCartoon']);
        Route::post('/animal_toon',[ImageAIController::class,'animalToon']);
        Route::post('/new_profile_pic',[ImageAIController::class,'newProfilePic']);
        Route::post('/funny_charactors',[ImageAIController::class,'funnyCharactors']);
    });
});

Route::get('/test/{id}',[ImageAIController::class,'test']);
Route::middleware('device_login')->group(function () {
    Route::post('/user',function (Request $request) {
        return $request->user();
    });
});
Route::prefix('users')->group(function () {
    Route::post('/register',[UserController::class,'register']);
    Route::post('/login',[UserController::class,'Login']);
    Route::post('/register-with-email',[UserController::class,'RegisterWithEmail']);
    Route::post('/login-with-email',[UserController::class,'LoginWithEmail']);
});

Route::post('/upload-zip', [BackgroundController::class, 'uploadAndUnzip']);