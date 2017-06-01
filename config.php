<?php
/**
 * config needed for the application to run
 */

$config = [

    "database_config" => [
        'database_type' => 'mysql',
        'database_name' => 'MyVideos107',
        'server' => '192.168.1.100',
        'username' => 'xbmc',
        'password' => 'xbmc',
        'logging' => true,
    ],

    "default_page_size" =>  20,

    "routes" => [
        [
            'routeGroup' => '/users',
            'table' => 'users'
        ]
    ]

]
?>