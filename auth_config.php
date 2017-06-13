<?php
/**
 * Created by PhpStorm.
 * User: taren
 * Date: 11-6-2017
 * Time: 14:01
 */

$config = [

  "database_config" => [
    'database_type' => 'mysql',
    'database_name' => 'phpJsonApi',
    'server' => '192.168.1.100',
    'username' => 'xbmc',
    'password' => 'xbmc',
    'logging' => true,
  ],

  "default_page_size" =>  20,

  "auth_url" => "http://localhost:5555",

  "routes" => [
    [
      'routeGroup' => '/users',
      'table' => 'users',
      'idField' => 'userId',
      'columns' => [
        'username',
        'email',
        'lastlogin',
      ],
//      'requestHandler' => function($request, $response, $args){
//        echo "custom request handler";
//      }
    ],
//    [
//      'routeGroup' => '/authenticate ',
//      'table' => 'sessions',
//      'idField' => 'sessionid',
//      'columns' => [
//        'sessionId',
//        'userid',
//        'ip',
//        'device',
//      ],
////      'requestHandler' => function($request, $response, $args){
////        echo "custom request handler";
////      }
//    ]
  ]

];
