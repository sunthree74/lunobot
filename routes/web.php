<?php
if (env('APP_ENV') === 'production') {
    URL::forceScheme('https');
}
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
    return view('auth.login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::resource('command', 'CommandController');

Route::get('/createSqlite', 'CommandController@createSqlite');
Route::get('/truncateSqlite', 'CommandController@truncateSqlite');

Route::post('webhook', 'CommandController@webhookUpdate')->name('webhook');

Route::get('setting', 'UserController@editPassword')->name('setting');
Route::post('password/change', 'UserController@changePassword')->name('password.change');
Route::get('toggleswitch/{filter}', 'UserController@toggleSwitch')->name('toggleswitch');

Route::get('/edit/welcome', 'CommandController@editWelcomeMessage')->name('welcome.edit');
Route::post('/update/welcome', 'CommandController@updateWelcomeMessage')->name('welcome.update');