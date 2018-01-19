<?php


//syncEngine

Route::post('channels/sync/add',  	  	['uses' => 'SyncChannelsController@add',  	'as' => 'sync.add']);
Route::put('channels/sync/edit/{id}', 	['uses' => 'SyncChannelsController@update', 'as' => 'sync.update']);
// Route::any('channels/sync/', 			['uses' => 'SyncChannelsController@syncEngine']);
Route::any('channels/sync/', 			['uses' => 'SyncChannelsController@storeFrom','as' => 'sync.update']);

