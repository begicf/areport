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


Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');

//Taxonomy
Route::group(['prefix' => 'taxonomy', 'middleware' => 'auth'], function () {
    Route::get('/upload', 'Taxonomy\TaxonomyController@index');
    Route::post('/upload', 'Taxonomy\TaxonomyController@store');

    Route::get('/managing', 'Taxonomy\TaxonomyController@managing');
    Route::post('/managing', 'Taxonomy\TaxonomyController@active');
    Route::post('/delete', 'Taxonomy\TaxonomyController@delete');
});

//Modules
Route::group(['prefix' => 'modules', 'middleware' => 'auth'], function () {
    Route::get('/', 'Modules\ModulesController@index')->name('Modules');
    Route::post('/json', 'Modules\ModulesController@json')->name('Modules');
    Route::post('/group', 'Modules\ModulesController@group')->name('Modules');
});

Route::group(['prefix' => 'table', 'middleware' => 'auth'], function () {
    Route::post('/', 'Table\TableController@table')->name('Modules');
    Route::post('/ajax', 'Table\TableController@renderTable')->name('Table');
    Route::post('/export', 'Table\TableController@exportTable')->name('Table Export');
    //Route::post('/json', 'Modules\ModulesController@json')->name('Modules');
});

//Route::get(['/','/home'], 'HomeController@index')->name('home');
