<?php

Route::get('package', function(){
	echo 'Hello from the usedesk package!';
});



    //syncEngine
// $api->get('/settings/channels/sync/create/', ['uses' => 'SyncChannelsController@getEditSyncEngine', 'as' => 'user.company_email_channels.get_create_sync_engine']);
// $api->post('/settings/channels/sync/create/', ['uses' => 'SyncChannelsController@postEditSyncEngine', 'as' => 'user.company_email_channels.post_create_sync_engine']);
// $api->get('/settings/channels/sync/edit/{id}', ['uses' => 'SyncChannelsController@getEditSyncEngine', 'as' => 'user.company_email_channels.get_edit_sync_engine']);
// $api->post('/settings/channels/sync/edit/{id}', ['uses' => 'SyncChannelsController@postEditSyncEngine', 'as' => 'user.company_email_channels.post_edit_sync_engine']);
// $api->any('/v1/syncEngine', ['uses' => 'SyncChannelsController@syncEngine']);
// $api->any('/v1/syncEngine', ['uses' => 'SyncChannelsController@saveFromSyncEngine']);
