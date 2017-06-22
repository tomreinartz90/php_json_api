<?php
namespace JsonApi;

use JsonApi\DatabaseHelpers;
use JsonApi\Core;
use Slim\App;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

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

  private $JWT_KEY = "MosXCKe:T_r6tu-jkZyX0,swz)[AgQJi0TpD-|n`NE]Zb]XgvAnL?b+0uC)~[U";
  /**
   * app constructor.
   */
  public function __construct($config)
  {
    $this->config = $config;
    parent::__construct($config);

    if(isset($config['JWT_KEY'])){
      $this->JWT_KEY = $config['JWT_KEY'];
    }

    $this->setRoutes();
  }

  private function setRoutes(){

    $authService = $this;

    //handle request to check token
    $this->slim->get('/is-authorized/{token}', function ($request, $response, $args) use ($authService) {
      return $authService->handleIsAuthRequest($request, $response, $args);
    });

    //handle request to create token
    $this->slim->get('/authorize', function ($request, $response, $args) use ($authService) {
      return $authService->handleLoginRequest($request, $response, $args);
    });



//    $this->routes = [
//      [
//        'route' => '/is-authorized',
//        'table' => 'sessions',
//        'idField' => 'token',
//        'columns' => [
//          'sessionstarted',
//          'email',
//          'lastlogin',
//        ],
//      ],
//      [
//        'route' => '/authorize',
//        'table' => 'users',
//        'idField' => 'userId',
//        'columns' => [
//          'userId',
//          'username',
//          'password',
//        ]
//      ],
//      [
//        'routeGroup' => '/users',
//        'table' => 'users',
//        'idField' => 'userId',
//        'columns' => [
//          'username',
//        ],
//        'requestHandler' => function($config, $request, $response, $args){return $this->handleUsersRequest($config, $request, $response, $args);}
//      ],
//    ];
  }

  public function run(){
    $this->slim->run();
  }

  protected function setupFromConfig()
  {
    $this->setupSlimRoutes($this->routes);
  }

  protected function getExpireDate(){
    return date("Y-m-d H:i:s", strtotime('+1 day'));
  }


  protected function handleLoginRequest($request, $response, $args){

    $parsedBody = $request->getParsedBody();
    if( isset($parsedBody['username']) && isset($parsedBody['password']) ){
      $database = new \JsonApi\DatabaseHelpers($this->config['database_config']);
      //get user from db.
      $database->getRecordInTable('users', []);
      //check password with salt.

      //return token or unauthorized
    } else {
      return $response->withStatus(400);
    }

    $user = $database->getRecordInTable('users', ['userId', 'username', 'lastlogin'], 'userId', $session['userId']);


    //return token.
    $response->write(json_encode($this->createToken([], 1)));

    return $response;

  }

  protected function handleIsAuthRequest($request, $response, $args){
    $user = null;
    if(isset($args['token'])){
      $database = new \JsonApi\DatabaseHelpers($this->config['database_config']);
      $session = $database->getRecordInTable('sessions', ['userId'], 'token', $args['token']);
      if( !is_null($session ) ){
        $user = $database->getRecordInTable('users', ['userId', 'username', 'lastlogin'], 'userId', $session['userId']);
      }
    }

    if(!is_null($user)){
      $response->withStatus(200)->write(json_encode($user));
    } else {
      $response->withStatus(500);
    }

    return $response;
  }

  protected function handleUsersRequest($config, $request, $response, $next){
    if($request->isPost()){
      $user = $request->getParsedBody();
      var_dump($user);
      //creation of users is only allowed with a password object.
      if(!isset($user['password'])){
//        return $response->withStatus(400);
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

      $request->getBody()->write(json_encode($user));
    }

    $response = $next($request, $response);
    return $response;
//    return $this->dataBaseMiddleWare($config, $request, $response, $next);
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



  private function createToken($user, $tokenId){
    $expireDate = $this->getExpireDate();
    $token = [
      "expiresOn" => $expireDate,
      "user" => $user,
      "tokenId" => $tokenId,
    ];
    $jwt = JWT::encode($token, $this->JWT_KEY);
    return $jwt;
  }

  private function decodeToken($jwt){
    return (array) JWT::decode($jwt, $this->JWT_KEY, array('HS256'));
  }

}