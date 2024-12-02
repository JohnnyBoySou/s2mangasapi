<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommentPostController;
use App\Http\Controllers\Api\CompletesController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\LikesController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\MangalistController;
use App\Http\Controllers\Api\RecoveryPassword;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/forget-password-code', [RecoveryPassword::class, 'forgetPasswordCode']);
Route::post('/forget-password-validate', [RecoveryPassword::class, 'forgetPasswordValidate']);
Route::post('/reset-password', [RecoveryPassword::class, 'resetPassword']);

// Rotas restritas
Route::middleware('auth:sanctum')->group(function () {
  Route::get('/statistics', [StatsController::class, 'getStatistics']);

  Route::post('/posts', [PostController::class, 'store']);  // Criar post
  Route::post('/posts/{post}/like', action: [PostController::class, 'like']);  // Dar ou remover like
  Route::get('/posts/most-liked', [PostController::class, 'mostLikedPosts']);
  Route::get('/posts/feed', action: [PostController::class, 'feed']);
  Route::post('/posts/{postId}/comments', [CommentPostController::class, 'store']);
  Route::get('/posts/{postId}/comments', [CommentPostController::class, 'index']);
  Route::put('/posts/comments/{commentId}', [CommentPostController::class, 'update']);
  Route::delete('/posts/comments/{commentId}', [CommentPostController::class, 'destroy']);

  Route::get('/profile/{id}/collections', [CollectionController::class, 'userSingleCollections']);
  Route::get('/profile/{id}/posts', [PostController::class, 'userSinglePosts']);
  Route::get('/profile/{id}/reviews', [ReviewController::class, 'userReviewsById']);
  Route::get('/profile/{id}', [UserController::class, 'userProfile']);


  Route::get('/user/posts', [PostController::class, 'userPosts']);
  Route::get('/user/reviews', [ReviewController::class, 'userReviews']);
  Route::get('/user/genres', [UserController::class, 'genres']);
  Route::get('/user/mangalist', [MangalistController::class, 'userMangalist']);
  Route::post('/users/logout', [LoginController::class, 'logout']);
  Route::put('/users/edit', [UserController::class, 'update']);
  Route::delete('/users/exclude', [UserController::class, 'destroy']);

  Route::get('/status/{id}', [StatusController::class, 'getStatus']);

 
  Route::post('/comments/{id}/like', [CommentController::class, 'like']);

  Route::post('/collections/includes', [CollectionController::class, 'includes']);
  Route::put('/collections/toggle/{collection}', [CollectionController::class, 'toggle']);
  Route::get('/collections/search/{search}', [CollectionController::class, 'search']);
  Route::post('/collections/{id}/like', [CollectionController::class, 'toggleLike']);
  Route::get('/collections/most-liked', action: [CollectionController::class, 'mostLikedCollections']);
  Route::post('/collection/fixed/{collection}', [CollectionController::class, 'toggleFixed']);
  Route::post('/collections/{collectionId}/upload-cover', [CollectionController::class, 'uploadCover']);

  Route::get('/library', [LibraryController::class, 'index']);
  Route::get('/library/{id}', [LibraryController::class, 'single']);
  Route::get('/follow', [FollowController::class, 'index']);
  Route::put('/follow', [FollowController::class, 'update']);
  Route::get('/completes', [CompletesController::class, 'index']);
  Route::put('/completes', [CompletesController::class, 'update']);
  Route::get('/progress', [ProgressController::class, 'index']);
  Route::put('/progress', [ProgressController::class, 'update']);
  Route::get('/likes', [LikesController::class, 'index']);
  Route::put('/likes', [LikesController::class, 'update']);


  Route::post('/follow/{id}', [UserController::class, 'follow']);
  Route::post('/unfollow/{id}', [UserController::class, 'unfollow']);
  Route::get('/followers/{id}', [UserController::class, 'followers']);
  Route::get('/following/{id}', [UserController::class, 'following']);
  Route::get('/is-following/{id}', [UserController::class, 'isFollowing']);
  Route::post('/toggleFollowing/{id}', [UserController::class, 'toggleFollowing']);

  Route::post('/reviews/{id}/feedback', [ReviewController::class, 'markHelpful']);

  Route::get('/manga/reviews/statistics/{mangaId}', [ReviewController::class, 'statistics']);
  Route::get('/manga/reviews/{mangaId}', [ReviewController::class, 'single']);

  Route::get('/posts', [PostController::class, 'allPosts']);
  Route::put('/posts/{post}', [PostController::class, 'update']);
  Route::delete('/posts/{post}', [PostController::class, 'destroy']);

  Route::get('/search/mangalist/{search}', [MangalistController::class, 'searchAll']);
  Route::get('/search/collection/{search}', [CollectionController::class, 'searchAll']);

  Route::post('/mangalist/{id}/like', [MangalistController::class, 'toggleLike']);


  Route::resource('/reviews', ReviewController::class);
  Route::resource('/users', UserController::class);
  Route::resource('/collections', CollectionController::class);
  Route::resource('/mangalist', MangalistController::class);
  Route::resource('/comments', CommentController::class);
});
