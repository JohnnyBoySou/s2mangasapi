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

//Rota pública
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);


Route::post('/forget-password-code', [RecoveryPassword::class, 'forgetPasswordCode']);
Route::post('/forget-password-validate', [RecoveryPassword::class, 'forgetPasswordValidate']);
Route::post('/reset-password', [RecoveryPassword::class, 'resetPassword']);


//Rota restrita
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/users/logout', [LoginController::class, 'logout']);
    Route::put('/users/edit', [UserController::class, 'update']);
  //  Route::get('/users/details', [UserController::class,'index']);
    Route::delete('/users/exclude', [UserController::class,'destroy']);
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
