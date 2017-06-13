<?php
require 'vendor/autoload.php';

//include 'auth/index.php';
use \JsonApi\Core;
require_once 'auth_config.php';

$app = new \JsonApi\Core($config);

$app->run();