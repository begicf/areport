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

// No public page: guests sign in, signed-in users go to their workspace.
Route::get('/', function () {
    return redirect(auth()->check() ? '/home' : '/login');
});

// Nothing here is meant for search engines.
Route::get('/robots.txt', function () {
    return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

Auth::routes(['verify' => true]);
Route::get('/home', 'Areport\HomeController@index')->name('home');
Route::middleware('auth')->group(function () {
    Route::get('/account/settings', 'Account\SettingsController@index')->name('account.settings');
    Route::post('/account/settings', 'Account\SettingsController@update')->name('account.settings.update');
});
Route::post('/areport/json', 'Areport\HomeController@json')->name('json');

//Taxonomy
Route::group(['prefix' => 'taxonomy', 'middleware' => 'auth'], function () {
    Route::get('/', function () {
        return redirect('/taxonomy/managing');
    });
    Route::get('/upload', 'Taxonomy\TaxonomyController@index');
    Route::post('/upload', 'Taxonomy\TaxonomyController@store');

    Route::get('/managing', 'Taxonomy\TaxonomyController@managing');
    Route::post('/managing', 'Taxonomy\TaxonomyController@active');
    Route::post('/delete', 'Taxonomy\TaxonomyController@delete');
    Route::post('/{taxonomy}/package', 'Taxonomy\TaxonomyController@addPackage')->whereNumber('taxonomy')->name('taxonomy.package');
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
    Route::post('/validate', 'Table\TableController@validateInstance');
    Route::get('/key-suggestions', 'Table\TableController@keySuggestions');
    Route::get('/prior-instances', 'Table\PriorPeriodController@instances');
    Route::get('/prior-values', 'Table\PriorPeriodController@values');
    Route::post('/copy-from', 'Table\PriorPeriodController@copy');
    Route::get('/validation-report', 'Table\TableController@validationReport')->name('validation.report');
    Route::get('/history', 'Table\TableController@history')->name('instance.history');
    Route::get('/open', 'Table\TableController@open')->name('instance.open');
    Route::get('/navigator', 'Table\TableController@navigator');

});

// Reporting entities (LEI, legal name and country from GLEIF)
Route::get('/stats.json', 'Stats\StatsController@json')->middleware('throttle:30,1')->name('stats.json');

Route::group(['prefix' => 'entities', 'middleware' => 'auth'], function () {
    Route::get('/', 'Entities\EntityController@index')->name('entities.index');
    Route::post('/', 'Entities\EntityController@store')->name('entities.store');
    Route::post('/lei-lookup', 'Entities\EntityController@lookup')->name('entities.lei-lookup');
    Route::patch('/{entity}/default', 'Entities\EntityController@setDefault')->whereNumber('entity')->name('entities.default');
    Route::patch('/{entity}', 'Entities\EntityController@update')->whereNumber('entity')->name('entities.update');
    Route::delete('/{entity}', 'Entities\EntityController@destroy')->whereNumber('entity')->name('entities.destroy');
});

//Export instance
Route::group(['prefix' => 'instance', 'middleware' => 'auth'], function () {
    Route::post('/export', 'Export\InstanceController@exportInstance');
    Route::post('/export-csv', 'Export\InstanceController@exportCsvInstance');
    Route::post('/preflight', 'Export\InstanceController@preflight');
    Route::post('/arelle', 'Export\InstanceController@arelleValidate');
    Route::get('/settings', 'Export\InstanceController@settings');
    Route::post('/settings', 'Export\InstanceController@updateSettings');
    Route::post('/import-report', 'Export\ReportImportController@store')->name('report.import');
    Route::get('/import-report/{instance}', 'Export\ReportImportController@status')->whereNumber('instance')->name('report.import.status');
    Route::delete('/{instance}', 'Export\InstanceController@destroy')->whereNumber('instance')->name('instance.destroy');

});

