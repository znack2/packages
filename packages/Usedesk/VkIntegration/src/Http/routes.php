<?php

Route::group(['middleware' => ['web', 'auth', 'social']], function() {
	//TODO: роуты для добавления каннала фб
	Route::post('channels/fb/add', 			['uses' => 'FbChannelsController@addChannel', 		'as' => 'fb.addChannel']);
	Route::post('channels/vk/add', 			['uses' => 'VkChannelsController@addVkChannel', 	'as' => 'vk.addChannel']);

	Route::put('channels/fb/check/{id}', 	['uses' => 'FbChannelsController@checkGroup', 		'as' => 'fb.checkGroup']);

	Route::post('channels/vk/createGroupChannel', ['uses' => 'VkChannelsController@createGroupChannel', 'as' => 'vk.createVkGroupChannel']);

	Route::post('channels/fb/create', 			['uses' => 'FbChannelsController@createChannel', 'as' => 'fb.createChannel']);
	Route::post('channels/vk/create', 			['uses' => 'VkChannelsController@createChannel', 'as' => 'vk.createChannel']);

	Route::any('channels/fb/callback', 			['uses' => 'FbChannelsController@getCallback', 	 'as' => 'fb.callback']);
	Route::any('channels/vk/callback/{id}', 	['uses' => 'VkChannelsController@getCallback', 	 'as' => 'vk.callback']);

	//vk

	// Route::get('channels/vk-token-renew/{id}', ['uses' => 'VkChannelsController@vkRenew', 'as' => 'user.company_email_channels.vk_renew']);
	// Route::get('channels/vk-token-renew-get-token/{id}', ['uses' => 'VkChannelsController@vkRenewGetToken', 'as' => 'user.company_email_channels.vk_renew_get_token']);

	// Route::post('channels/vk/edit', ['uses' => 'VkChannelsController@postEditVk', 'as' => 'user.company_email_channels.post_edit_vk']);
	// Route::post('channels/vk/edit/{id}', ['uses' => 'VkChannelsController@postEditVk', 'as' => 'user.company_email_channels.post_edit_vk']);
	// Route::get('channels/vkLogout', ['uses' => 'VkChannelsController@vkLogout', 'as' => 'user.company_email_channels.vkLogout']);
	// Route::any('/vk/group-key/{id}', ['uses' => 'VkChannelsController@getVkGroupKey', 'as' => 'user.vk.group_key']);
});