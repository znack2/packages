<?php

return [

    /**
     * types of notifications
     */
    'notification' => [
        'notify_from_messages' => getenv('NOTIFY_FROM_MESSAGES'),
    ],

    'telegram' => [
        'api_token' => '',
        'bot_username' => '',
        'channel_username' => '', // Channel username to send message
        'channel_signature' => '' // This will be assigned in the footer of message
    ],
    /**
     * Returns Twitter credentials from config file
     */
    'twitter' => [
        'twitter_consumer_key' => getenv('TWITTER_CONSUMER_KEY'),
        'twitter_consumer_secret' => getenv('TWITTER_CONSUMER_SECRET'),
        'twitter_access_token' => getenv('TWITTER_ACCESS_TOKEN'),
        'twitter_access_token_secret' => getenv('TWITTER_ACCESS_TOKEN_SECRET'),
    ],
    /**
     * Returns Facebook credentials from config file
     */
    'facebook' => [
        'facebook_apps_id' => getenv('FACEBOOK_APP_ID'),
        'facebook_app_secret' => getenv('FACEBOOK_APP_SECRET'),
        'facebook_page_id' => getenv('FACEBOOK_PAGE_ID'),
        'page_access_token' => ''
        'default_graph_version' => 'v2.10',
        //'enable_beta_mode' => true,
        //'http_client_handler' => 'guzzle',
    ]
    /**
     * Returns Facebook credentials from config file
     */
    'vk' => [
        'vk_apps_id' => getenv('VK_APP_ID'),
        'vk_app_secret' => getenv('VK_APP_SECRET'),
        'vk_page_id' => getenv('VK_PAGE_ID'),
        'page_access_token' => ''
        'default_graph_version' => 'v2.10',
        //'enable_beta_mode' => true,
        //'http_client_handler' => 'guzzle',
    ]
    /*
     * The default list of permissions that are
     * requested when authenticating a new user with your app.
     * The fewer, the better! Leaving this empty is the best.
     * You can overwrite this when creating the login link.
     *
     * Example:
     *
     * 'default_scope' => ['email', 'user_birthday'],
     *
     * For a full list of permissions see:
     *
     * https://developers.facebook.com/docs/facebook-login/permissions
     */
    'default_scope' => [],
    /*
     * The default endpoint that will redirect to after
     * an authentication attempt.
     */
    'default_redirect_uri' => '/facebook/callback',
];