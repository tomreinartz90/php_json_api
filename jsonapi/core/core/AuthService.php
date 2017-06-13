<?php
namespace JsonApi;

use JsonApi\DatabaseHelpers;
use JsonApi\Core;
use Slim\App;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Created by PhpStorm.
 * User: Reinartz.T
 * Date: 30-5-2017
 * Time: 14:39
 *
 * simple implementation of an auth service.
 * GET /is-authorized/{token} returns status 200/404 for valid or invalid token. The response body will be the information of the client.
 *
 * POST /authorize accepts a username and a password from the user table and checks if the data matches. If the user is allowed to login it returns a token that can be used for other requests.
 *
 * /sessions
 * GET get a list of sessions
 * GET /sessions/{sessionId} Get a specific session
 * Delete /sessions/{sessionId} Removes a specific session
 *
 * /users
 * GET gets a list of users
 * POST allows creation of users (requires the field password in the object)
 * PUT /users/{userId} allows update of the user (allows the field password in the object)
 * get /users/{userId} gets specific information about a user.
 */
class AuthService extends \JsonApi\Core
{
  public $slim;
  private $config;

  protected $routes = [];
  /**
   * app constructor.
   */
  public function __construct($config)
  {
    $this->setRoutes();
    parent::__construct($config);
  }

  private function setRoutes(){
    $this->routes = [
      [
        'route' => '/is-authorized',
        'table' => 'sessions',
        'idField' => 'token',
        'columns' => [
          'sessionstarted',
          'email',
          'lastlogin',
        ],
      ],
      [
        'route' => '/authorize',
        'table' => 'users',
        'idField' => 'userId',
        'columns' => [
          'userId',
          'username',
          'password',
        ]
      ],
      [
        'routeGroup' => '/users',
        'table' => 'users',
        'idField' => 'userId',
        'columns' => [
          'username',
        ],
        'requestHandler' => function($config, $request, $response, $args){return $this->handleUsersRequest($config, $request, $response, $args);}
      ],
    ];
  }

  public function run(){
    $this->slim->run();
  }

  protected function setupFromConfig()
  {
    $this->setupSlimRoutes($this->routes);
  }


  protected function handleLoginRequest(){

  }

  protected function handleIsAuthRequest(){

  }

  protected function handleUsersRequest($config, $request, $response, $args){
    if($request->isPost()){
      $user = $request->getParsedBody();
      //creation of users is only allowed with a password object.
      if(!isset($user['password'])){
        return $response->withStatus(400);
      }
    }

    /**
     * parse password to an encrypted variable
     */
    if( $request->isPost() OR ( $request->isPut() AND isset($user['password']) ) ){
      $user = $request->getParsedBody();
      array_push($config['columns'], 'password');
      array_push($config['columns'], 'secret');

      $secret = $this->generateHash();
      $password = hash_hmac('ripemd160', $user['password'], $secret);

      $user['secret'] = $secret;
      $user['password'] = $password;
      var_dump($user);

      $request->write(json_encode($user));
    }
    return $this->requestHandler($config, $request, $response, $args);
  }

  /**
   * simple function to generate a hash.
   */
  private function generateHash() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < 50; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    $hash = hash('ripemd160', date('Y-m-d H:i:s') +  $randomString +  microtime(true));

    return $hash;
  }

}