<?php

Route::group(['middleware' => ['web', 'auth', 'CanViewCRM']], function() {
    // Dashboard (overview) routes
    Route::get('/fb/dashboard',                 'DashboardController@index');
    Route::get('/fb/launch',                    'DashboardController@launch');
    // Helper routes
    Route::get('/fb/help',                      'DashboardController@help');
    Route::get('/fb/help/disable',              'DashboardController@disableHelp');
    // Facebook login routes
    Route::get('/fb/facebook',                  'FacebookLoginController@askFbPermissions');
    Route::get('/fb/callback',                  'FacebookLoginController@fbCallback');
    // Publish routes
    Route::get('/fb/publisher',                 'PublishController@index');
    Route::get('/fb/publisher/{id}',            'PublishController@detail');
    Route::post('/fb/publish',                  'PublishController@publish');
    Route::get('/fb/publishment/{id}/delete',   'PublishController@delete');
    Route::get('/fb/reaction/{id}/delete',      'PublishController@deleteReaction');
    Route::post('/fb/publisher/{id}/answer',    'PublishController@replyTweet');
    Route::post('/fb/publisher/{id}/post',      'PublishController@replyPost');
    // Cases routes (1/3) - general
    Route::get('/fb/cases',                     'CasesController@index'); // Dashboard overview
    Route::get('/fb/cases/filter',              'CasesController@filter'); // Filter dashboard overview
    Route::get('/fb/case/{id}',                 'CasesController@detail'); // Detail page of a specific case
    Route::get('/fb/case/{caseId}/close',       'CasesController@toggleCase'); // Close or re-open a case
    // Cases routes (2/3) - Facebook related
    Route::post('/fb/answer/{id}/post',         'CasesController@replyPost'); // Reply to a Facebook post
    Route::post('/fb/answer/reply/{id}',        'CasesController@replyPrivate'); // Reply to a Facebook post
    Route::get('/fb/case/{caseId}/post/{messageId}', 'CasesController@deletePost'); // Delete Facebook post
    Route::get('/fb/case/{caseId}/inner/{messageId}', 'CasesController@deleteInner'); // Delete inner Facebook post
    // Cases routes (3/3) - Twitter related
    Route::post('/fb/answer/{id}',              'CasesController@replyTweet'); // Reply to a tweet
    Route::get('/fb/case/{caseId}/tweet/{messageId}', 'CasesController@deleteTweet'); // Delete a tweet
    Route::get('/fb/case/{caseId}/follow',      'CasesController@toggleFollowUser'); // Follow a user on Twitter
    // Case summary routes
    Route::post('/fb/case/{id}/summary/add',    'SummaryController@addSummary'); // Add a case summary
    Route::get('/fb/case/{id}/summary/{summaryId}/delete', 'SummaryController@deleteSummary'); // Delete a case summary
    // User management routes
    Route::get('/fb/users',                     'UsersController@index'); // Team overview
    Route::get('/fb/user/add',                  'UsersController@addUser'); // Add user
    Route::post('/fb/user/add',                 'UsersController@postUser'); // Team overview
    Route::post('/fb/user/{id}',                'UsersController@toggleUser'); // Auth/de-auth user to see CRM
    Route::get('/fb/users/filter',              'UsersController@searchUser'); // Search user by name/e-mail
});