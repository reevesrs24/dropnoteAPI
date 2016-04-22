<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'api'], function()
{
    Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);

    Route::post('authenticate', 'AuthenticateController@authenticate');

});


Route::group(['prefix' => 'user'], function()
{
	Route::post('addUser', 'UserController@registerUser');

	Route::post('findUserByUsername', 'UserController@findUserByUsername');

	Route::post('getFriends', 'UserController@getFriends');

	Route::post('addFriend', 'UserController@addToFriends');

	Route::post('deleteFriend', 'UserController@deleteFriend');

	Route::post('findUserByPhoneNumber', 'UserController@findUserByPhoneNumber');
});

Route::group(['prefix' => 'messages'], function()
{
	Route::post('getAllMessages', 'MessageController@getAllMessages');

	Route::post('postMessage', 'MessageController@postMessage');

	Route::post('getFriendsMessages', 'MessageController@getFriendsMessages');

});



