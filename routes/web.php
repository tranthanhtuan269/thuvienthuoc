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

Route::get('/resetStatus', [HomeController::class, 'resetStatus']);
Route::get('/link', [HomeController::class, 'linkError']);
Route::get('/detail', [HomeController::class, 'detail']);
Route::get('/chap', [HomeController::class, 'chap']);
Route::get('/exist', [HomeController::class, 'exist']);


