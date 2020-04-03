<?php

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

use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});



//Tax
Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');


Route::get('/upload', 'Upload\UploadController@index');
Route::post('/upload', 'Upload\UploadController@store');

Route::get('/managing', 'Upload\UploadController@managing');
Route::post('/managing', 'Upload\UploadController@active');
Route::post('/tax_delete', 'Upload\UploadController@delete');

//Modules
Route::group(['prefix' => 'modules', 'middleware' => 'auth'], function () {
    Route::get('/', 'Modules\ModulesController@index')->name('Modules');
    Route::post('/json', 'Modules\ModulesController@json')->name('Modules');
});

//Route::get(['/','/home'], 'HomeController@index')->name('home');
