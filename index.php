<?php
require 'vendor/autoload.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Headers: Origin, x-auth-token, X-Requested-With, Content-Type, Accept");
//header("Access-Control-Allow-Headers: *");
///header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');

//set default timezone, should be the same as mysql database
//date_default_timezone_set('Europe/Berlin');

require_once 'config.php';
require_once 'PHP_JSON_API/app.php';

$app = new app($config);


$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write(' Hello ');
    return $response;
});

