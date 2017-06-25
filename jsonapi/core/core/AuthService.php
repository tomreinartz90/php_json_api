<?php

  namespace JsonApi;

  use Firebase\JWT\JWT;

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
  require_once "templates/AuthTemplate.php";

  class AuthService extends \JsonApi\Core
  {
    public $slim;
    private $config;

    protected $routes = [];

    private $JWT_KEY = "MosXCKe:T_r6tu-jkZyX0,swz)[AgQJi0TpD-|n`NE]Zb]XgvAnL?b+0uC)~[U";
    private $ip;
    private $device;

    /**
     * app constructor.
     */
    public function __construct( $config )
    {
      $this -> config = $config;
      $this -> ip = $_SERVER[ 'REMOTE_ADDR' ];;
      $this -> device = $_SERVER[ 'HTTP_USER_AGENT' ];;

      parent ::__construct( $config );

      if ( isset( $config[ 'JWT_KEY' ] ) ) {
        $this -> JWT_KEY = $config[ 'JWT_KEY' ];
      }

      $this -> setRoutes();
    }

    private function setRoutes()
    {

      $authService = $this;

      //handle request to check token
      $this -> slim -> get( '/is-authorized/{token}', function ( $request, $response, $args ) use ( $authService ) {
        return $authService -> handleIsAuthRequest( $request, $response, $args );
      } );


      //handle authorize request for GET and POST
      $this -> slim -> post( '/authorize', function ( $request, $response, $args ) use ( $authService ) {
        return $authService -> handleAuthorizeRequest( $request, $response, $args );
      } );
      $this -> slim -> get( '/authorize', function ( $request, $response, $args ) use ( $authService ) {
        return $authService -> handleAuthorizeRequest( $request, $response, $args );
      } );


      //handle request to create token
      $this -> slim -> post( '/token', function ( $request, $response, $args ) use ( $authService ) {
        return $authService -> handleTokenRequest( $request, $response, $args );
      } );


      //handle user login request (via redirect of /authorize
      $this -> slim -> get( '/authorize/login', function ( $request, $response, $args ) use ( $authService ) {
        return $authService -> handleUserLoginRequest( $request, $response, $args );
      } );
      //handle request to create token
      $this -> slim -> post( '/authorize/login', function ( $request, $response, $args ) use ( $authService ) {
        return $authService -> handleUserLoginRequest( $request, $response, $args );
      } );

    }

    public function run()
    {
      $this -> slim -> run();
    }

    protected function setupFromConfig()
    {
      $this -> setupSlimRoutes( $this -> routes );
    }

    protected function getExpireDate()
    {
      return date( "Y-m-d H:i:s", strtotime( '+1 day' ) );
    }


    protected function handleTokenRequest( $request, $response, $args )
    {
      $database = new \JsonApi\DatabaseHelpers( $this -> config[ 'database_config' ] );
      $queryParams = $request -> getParsedBody();
//      $requestBody = $request -> getParsedBody();

      if ( isset( $queryParams[ 'code' ] ) ) {
        $session = $database -> getRecordInTable( 'sessions', "*", 'uaa', $queryParams[ 'code' ] );
        if ( $session ) {
          $user = $database -> getRecordInTable( 'users', "*", 'userId', $session[ 'userId' ] );

          return $response ->
          withHeader( 'Content-Type', 'application/json' ) ->
          write( json_encode( $this -> getTokenObject( $session[ 'token' ], $user, $session[ 'clientId' ], $session[ 'scope' ] ) ) );
        }
      }

      return $response -> withStatus( 401 );
    }

    protected function handleAuthorizeRequest( $request, $response, $args )
    {
      $database = new \JsonApi\DatabaseHelpers( $this -> config[ 'database_config' ] );
      $queryParams = $request -> getQueryParams();

      /**
       * check if current client_id, scope, redirect_uri, ip already have a session
       * If that is the condition we just return a new token.
       */
      if ( isset( $queryParams[ 'client_id' ] ) && isset( $queryParams[ 'scope' ] ) && isset( $queryParams[ 'redirect_uri' ] ) ) {

        $sessions = $database -> handler -> select( 'sessions', [ 'uaa' ], [
          "AND" => [
            "clientId"     => $queryParams[ 'client_id' ],
            "scope"        => $queryParams[ 'scope' ],
            "redirect_uri" => $queryParams[ 'redirect_uri' ],
            "ip"           => $this -> ip,
            "active"       => true,
            "device"       => $this -> device
          ]
        ] );

        if ( isset( $sessions ) && count( $sessions ) > 0 ) {
          return $this -> redirect( $response, $queryParams[ 'redirect_uri' ] . "/?code=" . $sessions[0]['uaa'] );
        }

      }

      /**
       * there was no session found, lets create a new inactie one and redirect to the login page.
       */
      $uaa = $this -> generateHash( null, json_encode( $queryParams ) );
      $database -> addRecordInTable( 'sessions', [ "clientId", "scope", "redirect_uri", "ip", "active", "device", "uaa" ], [
        "clientId"     => $queryParams[ 'client_id' ],
        "scope"        => $queryParams[ 'scope' ],
        "redirect_uri" => $queryParams[ 'redirect_uri' ],
        "ip"           => $this -> ip,
        "active"       => false,
        "device"       => $this -> device,
        "uaa"          => $uaa
      ], 'sessionId' );

      return $this -> redirect( $response, $request -> getUri() -> getPath() . "/login?uaa=" . $uaa );

    }

    protected function handleUserLoginRequest( $request, $response, $args )
    {
      $authTemplate = new \AuthTemplate();

      if ( $request -> isGet() ) {
        return $response -> write( $authTemplate -> getLoginTemplate() );

      } else {

        $database = new \JsonApi\DatabaseHelpers( $this -> config[ 'database_config' ] );
        $queryParams = $request -> getQueryParams();
        $requestBody = $request -> getParsedBody();

        if ( isset( $queryParams[ 'uaa' ] ) && isset( $requestBody[ 'email' ] ) && isset( $requestBody[ 'password' ] ) ) {
          //get user from db.
          $userSession = $database -> getRecordInTable( 'sessions', '*', 'uaa', $queryParams[ 'uaa' ] );
          $user = $database -> getRecordInTable( 'users', '*', 'email', $requestBody[ 'email' ] );

          //check secret with salt.
          if ( $userSession != null && $user != null ) {
            $hashedPassword = $this -> generateHash( $requestBody[ 'password' ], $user[ 'salt' ] );

            //check if user password is correct.
            if ( $hashedPassword == $user[ 'password' ] ) {
              $user = $userSession;
              $token = $this -> createToken( $user, $user[ 'clientId' ], $userSession[ 'scope' ] );

              $sessionData = [
                "token"  => $token,
                "active" => true,
                "userId" => $user['id']
              ];


              //store session in the database
              $database -> updateRecordInTable( 'sessions', [ "token", "active", "userId" ], $sessionData, 'uaa', $queryParams[ 'uaa' ] );

              return $this -> redirect( $response, $userSession[ 'redirect_uri' ] . "/?code=" . $queryParams[ 'uaa' ] );

            }
          }
        }

        //redirect to the same page
        if ( isset( $requestBody[ 'email' ] ) ) {
          return $this -> redirect( $response, $request -> getUri() -> getPath() . "?uaa=" . $queryParams[ 'uaa' ] . "&error=loginError" );
        } else {
          //unauthorize
          return $response -> withStatus( 401 );
        }

      }
    }

    protected function handleIsAuthRequest( $request, $response, $args )
    {
      $user = null;
      if ( isset( $args[ 'token' ] ) ) {
        $database = new \JsonApi\DatabaseHelpers( $this -> config[ 'database_config' ] );
        $session = $database -> getRecordInTable( 'sessions', [ 'userId' ], 'token', $args[ 'token' ] );
        if ( !is_null( $session ) ) {
          $user = $database -> getRecordInTable( 'users', [ 'userId', 'username', 'lastlogin' ], 'userId', $session[ 'userId' ] );
        }
      }

      if ( !is_null( $user ) ) {
        $response -> withStatus( 200 ) -> write( json_encode( $user ) );
      } else {
        $response -> withStatus( 500 );
      }

      return $response;
    }

    protected function handleUsersRequest( $config, $request, $response, $next )
    {
      if ( $request -> isPost() ) {
        $user = $request -> getParsedBody();
        var_dump( $user );
        //creation of users is only allowed with a password object.
        if ( !isset( $user[ 'password' ] ) ) {
//        return $response->withStatus(400);
        }
      }

      /**
       * parse password to an encrypted variable
       */
      if ( $request -> isPost() OR ( $request -> isPut() AND isset( $user[ 'password' ] ) ) ) {
        $user = $request -> getParsedBody();
        array_push( $config[ 'columns' ], 'password' );
        array_push( $config[ 'columns' ], 'secret' );

        $secret = $this -> generateHash();
        $password = hash_hmac( 'ripemd160', $user[ 'password' ], $secret );

        $user[ 'secret' ] = $secret;
        $user[ 'password' ] = $password;
        var_dump( $user );

        $request -> getBody() -> write( json_encode( $user ) );
      }

      $response = $next( $request, $response );
      return $response;
//    return $this->dataBaseMiddleWare($config, $request, $response, $next);
    }

    /**
     * simple function to generate a hash.
     */
    private function generateHash( $hashString, $salt )
    {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen( $characters );
      $hashData = '';
      $hash = '';

      if ( !isset( $hashString ) and !is_null( $hashData ) ) {
        $randomString = '';
        for ( $i = 0; $i < 50; $i++ ) {
          $hashData .= $characters[ rand( 0, $charactersLength - 1 ) ];
        }
        $hashData = date( 'Y-m-d H:i:s' ) . $randomString . microtime( true );
      } else {
        $hashData = $hashString;
      }

      $hash = hash( 'sha256', $hashData . "" . $this -> JWT_KEY );

      if ( isset( $salt ) ) {
        $hash = hash( 'sha256', $hash . "" . $salt );
      }

      return $hash;
    }

    private function redirect( $response, $location )
    {
      return $response = $response -> withRedirect( $location );
    }


    private function createToken( $user, $clientId, $scope )
    {
      unset( $user[ 'salt' ] );
      unset( $user[ 'password' ] );

      $expireDate = $this -> getExpireDate();
      $token = [
        "expiresOn" => $expireDate,
        "user"      => $user,
        "scope"     => $scope,
        "clientId"  => $clientId,
      ];

      $jwt = JWT ::encode( $token, $this -> JWT_KEY );
      return $jwt;
    }

    private function getTokenObject( $token, $user, $clientId, $scope )
    {
      return [
        "access_token" => $token,
        "token_type"   => "bearer",
        "expiresOn"    => $this -> getExpireDate(),
        "clientId"     => $clientId,
        "scope"        => $scope,
        "uid"          => $user[ 'clientId' ],
        "info"         => $user,
      ];

    }

    private function decodeToken( $jwt )
    {
      return (array)JWT ::decode( $jwt, $this -> JWT_KEY, [ 'HS256' ] );
    }

  }
