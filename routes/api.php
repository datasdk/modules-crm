<?php

use Orion\Facades\Orion;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'api.crm.',
    'prefix' => 'crm',
], function () {
    Route::middleware(['auth.both:api'])->group(function () {
        Orion::resource('users', 'Api\UserController', ['only' => ['index', 'show', 'search']]);
        Orion::resource('user', 'Api\UserController', ['only' => ['index', 'show', 'search']]);
        Route::get('users/exists/{value}', 'Api\UserController@exists')->name('users.exists');
    });

    Route::middleware(['auth:api'])->group(function () {
        Orion::resource('users', 'Api\UserController', ['except' => ['index', 'show', 'search']]);
        Orion::resource('user', 'Api\UserController', ['except' => ['index', 'show', 'search']]);
    });
});
