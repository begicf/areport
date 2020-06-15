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

Route::get('/', function () {
    return view('welcome');
});


Auth::routes();
Route::get('/home', 'Areport\HomeController@index')->name('home');
Route::post('/areport/json', 'Areport\HomeController@json')->name('json');

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
    Route::get('/', 'Modules\ModulesController@index');
    Route::post('/json', 'Modules\ModulesController@json');
    Route::post('/group', 'Modules\ModulesController@group');
});

Route::group(['prefix' => 'table', 'middleware' => 'auth'], function () {
    Route::post('/', 'Table\TableController@table');
    Route::post('/ajax', 'Table\TableController@renderTable');
    Route::post('/export', 'Table\TableController@exportTable');
    Route::post('/import', 'Table\TableController@importTable');
    Route::post('/save', 'Table\TableController@saveTable');
    Route::post('/get_data', 'Table\TableController@getData');

});


