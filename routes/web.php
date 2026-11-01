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

// SEO: only the landing page is meant to be indexed; the workspace sits behind sign-in.
Route::get('/robots.txt', function () {
    $body = "User-agent: *\nAllow: /\$\nDisallow: /home\nDisallow: /table\nDisallow: /taxonomy\nDisallow: /modules\nDisallow: /instance\nDisallow: /pro\nDisallow: /password\n\nSitemap: ".url('/sitemap.xml')."\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});
Route::get('/sitemap.xml', function () {
    $lastmod = date('Y-m-d', (int) @filemtime(resource_path('views/welcome.blade.php')) ?: time());
    $urls = [[url('/'), '1.0', 'weekly']];   // sign-in pages are noindex
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    foreach ($urls as [$loc, $priority, $freq]) {
        $xml .= "  <url><loc>".e($loc)."</loc><lastmod>{$lastmod}</lastmod><changefreq>{$freq}</changefreq><priority>{$priority}</priority></url>\n";
    }

    return response($xml."</urlset>\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
});

// Public contact form: at most 3 messages per IP in 10 minutes (plus honeypot / timing checks).
Route::post('/contact', 'Contact\ContactController@store')->middleware('throttle:3,10')->name('contact.store');


Auth::routes(['verify' => true]);
Route::get('/home', 'Areport\HomeController@index')->name('home');
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

//Export instance
Route::group(['prefix' => 'instance', 'middleware' => 'auth'], function () {
    Route::post('/export', 'Export\InstanceController@exportInstance');
    Route::post('/export-csv', 'Export\InstanceController@exportCsvInstance');
    Route::post('/preflight', 'Export\InstanceController@preflight');
    Route::post('/arelle', 'Export\InstanceController@arelleValidate');
    Route::get('/settings', 'Export\InstanceController@settings');
    Route::post('/settings', 'Export\InstanceController@updateSettings');

});

