<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CompletesController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\LikesController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\MangalistController;
use App\Http\Controllers\Api\RecoveryPassword;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/forget-password-code', [RecoveryPassword::class, 'forgetPasswordCode']);
Route::post('/forget-password-validate', [RecoveryPassword::class, 'forgetPasswordValidate']);
Route::post('/reset-password', [RecoveryPassword::class, 'resetPassword']);
Route::get('/comments/{mangaId}', [CommentController::class, 'index']);

// Rotas restritas
Route::middleware('auth:sanctum')->group(function () {
  Route::post('/comments/{id}/like', [CommentController::class, 'like']);
  Route::post('/comments', [CommentController::class, 'store']);
  Route::put('/comments/{id}', [CommentController::class, 'update']);
  Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

  Route::post('/users/logout', [LoginController::class, 'logout']);
  Route::put('/users/edit', [UserController::class, 'update']);
  Route::delete('/users/exclude', [UserController::class, 'destroy']);

  // Rotas de recursos
  Route::resource('/users', UserController::class);
  Route::resource('/collections', CollectionController::class);
  Route::resource('/mangalist', MangalistController::class);

  // Rotas adicionais
  Route::put('/collections/toggle/{collection}', [CollectionController::class, 'toggle']);
  Route::get('/follow', [FollowController::class, 'index']);
  Route::put('/follow', [FollowController::class, 'update']);
  Route::get('/completes', [CompletesController::class, 'index']);
  Route::put('/completes', [CompletesController::class, 'update']);
  Route::get('/progress', [ProgressController::class, 'index']);
  Route::put('/progress', [ProgressController::class, 'update']);
  Route::get('/likes', [LikesController::class, 'index']);
  Route::put('/likes', [LikesController::class, 'update']);
});
