<?php

use Illuminate\Support\Facades\Route;


Route::view('/privacy-policy', 'privacy-policy');
Route::view('/', 'mangalists.index')->name('mangalists.index');
Route::view('/mangalist/{id}', 'mangalists.show')->name('mangalists.show');
 