<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\LikesController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\MangalistController;
use App\Http\Controllers\Api\RecoveryPassword;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Prompts\Progress;


//Rota pública
Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/forget-password-code', [RecoveryPassword::class, 'forgetPasswordCode']);
Route::post('/forget-password-validate', [RecoveryPassword::class, 'forgetPasswordValidate']);
Route::post('/reset-password', [RecoveryPassword::class, 'resetPassword']);


Route::post('/register', [UserController::class, 'store']);

//Rota restrita
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/logout/{user}', [LoginController::class, 'logout'])->name('');

    Route::resource('/users', UserController::class);
    Route::resource('/collections', CollectionController::class);
    Route::resource('/mangalist', MangalistController::class);
    
    Route::put('/collections/toggle/{collection}', [CollectionController::class, 'toggle']);
    Route::get('/follow', [FollowController::class, 'index']);
    Route::put('/follow', [FollowController::class, 'update']);
    Route::get('/completes', [FollowController::class, 'index']);
    Route::put('/completes', [FollowController::class, 'update']);
    Route::get('/progress', [ProgressController::class, 'index']);
    Route::put('/progress', [ProgressController::class, 'update']);
    Route::get('/likes', [LikesController::class, 'index']);
    Route::put('/likes', [LikesController::class, 'update']);

});


// Route::post('/collections', [CollectionController::class,'store']); //POST - /collections
// Route::get('/collections', [CollectionController::class,'index']); //GET - /collections?page=1
// Route::get('/collections/{collection}', [CollectionController::class,'show']); //GET - /collections/:id
