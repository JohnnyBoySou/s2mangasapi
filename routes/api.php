<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RecoveryPassword;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

//Route::get('/users', [UserController::class, 'index']); //GET - /users?page=1
//Route::get('/users/{user}', [UserController::class, 'show']); //GET - /users/id
//Route::post('/users', [UserController::class,'store']); //POST - /users
//Route::put('/users/{user}', [UserController::class,'update']); //PUT - /users/id
//Route::delete('/users/{user}', [UserController::class,'destroy']); //DELETE - /users/id

//Rota pública
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/forget-password-code', [RecoveryPassword::class,'forgetPasswordCode']);
Route::post('/forget-password-validate', [RecoveryPassword::class,'forgetPasswordValidate']);
Route::post('/reset-password', [RecoveryPassword::class,'resetPassword']);
Route::post('/users', [UserController::class,'store']);


//Rota restrita
Route::group(['middleware' => ['auth:sanctum']], function (){
    Route::get('/collections', [CollectionController::class,'list']);
    Route::post('/logout/{user}', [LoginController::class,'logout'])->name('');
    Route::get('/users', [UserController::class,'index']);
    Route::get('/users/{user}', [UserController::class,'show']);
    Route::put('/users/{user}', [UserController::class,'update']); 
    Route::delete('/users/{user}', [UserController::class,'destroy']);
});

// Route::post('/collections', [CollectionController::class,'store']); //POST - /collections
// Route::get('/collections', [CollectionController::class,'index']); //GET - /collections?page=1
// Route::get('/collections/{collection}', [CollectionController::class,'show']); //GET - /collections/:id
