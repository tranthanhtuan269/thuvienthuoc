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

Route::get('/', [HomeController::class, 'index']);
Route::get('/crawl', [HomeController::class, 'crawl']);
Route::get('/crawl-thuoc', [HomeController::class, 'crawl2']);
Route::get('/crawl-anh', [HomeController::class, 'crawl3']);
Route::get('/process', [HomeController::class, 'processThuoc']);
Route::get('/test', [HomeController::class, 'test']);
