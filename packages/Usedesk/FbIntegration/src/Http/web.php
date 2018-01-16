<?php


//TODO: роуты для добавления каннала фб
Route::get('/settings/channels/addFbChannel', ['uses' => 'FbChannelsController@addFbChannel', 'as' => 'user.company_email_channels.addFbChannel']);
Route::get('/settings/channels/checkFbGroup/{id}', ['uses' => 'FbChannelsController@checkFbGroup', 'as' => 'user.company_email_channels.checkFbGroup']);
Route::get('/settings/channels/createFbChannel', ['uses' => 'FbChannelsController@createFbChannel', 'as' => 'user.company_email_channels.createFbChannel']);
Route::any('/fb/callback', ['uses' => 'FbChannelsController@getFbCallback', 'as' => 'user.fb.callback']);
