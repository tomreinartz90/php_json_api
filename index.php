<?php

require 'vendor/autoload.php';
require_once 'PHP_JSON_API/PhpJsonApi.php';
require_once 'config.php';

$app = new PhpJsonApi($config);
$app->run();