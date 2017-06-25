<?php
  require 'vendor/autoload.php';

//include 'auth/index.php';
  use \JsonApi\Core;
  require_once 'config.php';

  $app = new \JsonApi\Core($config);
  $app->slim->add(new \JsonApi\GoogleOAuth2());
  $app->run();

  ?>