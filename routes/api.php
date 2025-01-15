<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommentPostController;
use App\Http\Controllers\Api\CompletesController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\MangaController;
use App\Http\Controllers\Api\MangadexController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\LikesController;
use App\Http\Controllers\Api\MangalistController;
use App\Http\Controllers\Api\RecoveryPassword;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WallpaperController;
use App\Http\Controllers\Api\ChapterController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/login', [UserController::class, 'login']);
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
  Route::get('/user/mangalists', [MangalistController::class, 'user']);
  Route::get('/user/manga', [MangaController::class, 'user']);
  Route::get('/user/wallpapers', [WallpaperController::class, 'user']);

  Route::get('/user/collections', [CollectionController::class, 'userCollections']);
  Route::post('/users/logout', [UserController::class, 'logout']);
  Route::put('/users/edit', [UserController::class, 'update']);
  Route::delete('/users/exclude', [UserController::class, 'destroy']);
  Route::get('/users/search', [UserController::class, 'search']);

  Route::get('/status/{id}', [StatusController::class, 'getStatus']);

  Route::post('/comments/{id}/like', [CommentController::class, 'like']);

  Route::post('/collections/includes', [CollectionController::class, 'includes']);
  Route::put('/collections/toggle/{collection}', [CollectionController::class, 'toggle']);
  Route::get('/collections/search/{search}', [CollectionController::class, 'search']);
  Route::post('/collections/{id}/like', [CollectionController::class, 'toggleLike']);
  Route::get('/collections/most-liked', action: [CollectionController::class, 'mostLikedCollections']);
  Route::post('/collections/{collection}/fixed', [CollectionController::class, 'toggleFixed']);
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
  Route::get('/search/user', [UserController::class, 'search']);
  Route::get('/search/manga', [MangaController::class, 'search']);

  Route::get('/mangadex/{id}', [MangadexController::class, 'search']);  
  Route::get('/mangadex/{id}/covers', [MangaController::class, 'getCovers']);  
  Route::get('/mangadex/search/{name}', [MangadexController::class, 'searchByName']);
  
  Route::post('/mangalist/{id}/like', [MangalistController::class, 'like']);
  
  Route::post('/manga/{id}/views', [MangaController::class, 'views']);
  Route::post('/manga/{id}/like', [MangaController::class, 'like']);
  Route::get('/manga/search', [MangaController::class, 'search']);
  Route::get('/manga/{id}/chapters', [ChapterController::class, 'listChapters']);
  Route::get('/manga/{id}/pages', [ChapterController::class, 'listPages']);

  Route::get('/manga/top', [MangaController::class, 'top']);
  Route::get('/manga/weekend', [MangaController::class, 'weekend']);
  Route::get('/manga/new', [MangaController::class, 'new']);
  Route::get('/manga/feed', [MangaController::class, 'feed']);
  
  //DASHBOARD
  Route::get('/dashboard/mangas', [MangaController::class, 'statistics']);
  Route::get('/dashboard/mangalists', [MangalistController::class, 'statistics']);
  
  //Route::post('/wallpapers/create', [WallpaperController::class, 'store']);
  Route::post('/mangalist/{id}/remove', [MangalistController::class, 'removeManga']);
  Route::put('/wallpaper/{id}/remove', [WallpaperController::class, 'remove']);
  Route::post('/wallpaper/{id}/add', [WallpaperController::class, 'add']);

  Route::resource('/feeds', FeedController::class);
  Route::resource('/reviews', ReviewController::class);
  Route::resource('/users', controller: UserController::class);
  Route::resource('/collections', CollectionController::class);
  Route::resource('/mangalists', MangalistController::class);
  Route::resource('/comments', CommentController::class);
  Route::resource('/wallpapers', WallpaperController::class);
  Route::resource('/manga', MangaController::class);
});
