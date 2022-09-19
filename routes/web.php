<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\HomeController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/crawl', [HomeController::class, 'crawlLink']);
Route::get('/link', [HomeController::class, 'linkError'])->name('abc');
Route::get('/detail', [HomeController::class, 'detail']);

