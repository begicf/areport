<?php
Route::get('cal', function(){
    echo 'Hello from the calculator package!';
});


Route::get('add/{a}/{b}', 'simpleMenu\MenuController@add');
Route::get('subtract/{a}/{b}', 'simpleMenu\MenuController@subtract');
