<?php

return [
    'users_route_prefix' => 'users', //set this prefix to anything that you wish to use for users profile routes
    'pagination_count' => 10,
    'css_framework' => 'tailwind', // or 'bootstrap'
    'comment_nesting' => true, // set to false if you don't want to allow nesting of comments
    'read_only' => false, // set to true if you want to make comments read only
    'default_sort' => 'newest', // newest, oldest, most_liked, most_replied
    'enable_sorting' => true, // set to false to disable sorting functionality
    'enable_reporting' => true, // set to false to disable comment reporting
    'report_reasons' => ['spam', 'inappropriate', 'offensive', 'other'], // predefined report reasons (optional, currently using free text)
    'theme' => 'auto', // light, dark, auto - controls theme mode for comment components
    'enable_emoji_picker' => true, // set to false to disable emoji picker
    'enable_notifications' => false, // set to true to enable notifications for comment events
    'notification_channels' => ['database'], // available: database, mail, broadcast
];
