<?php

  namespace JsonApi;

  use Google_Client;
  use Google_Service_Oauth2;
  use GuzzleHttp\Client;

  class GoogleOAuth2
  {


    function __construct()
    {
    }

    /**
     *
     * invoke the request and trigger the Oauth flow.
     * @param $request
     * @param $response
     * @param $next
     * @return mixed
     */
    public function __invoke( $request, $response, $next )
    {

//      $this->cache_set("boo", "bah");
//      return $response->write($this->cache_get("bah"));

      return $this -> handleAuth( $request, $response, $next );
    }


    function handleAuth( $request, $response, $next )
    {
      $oauth_credentials = null;

      /**
       * get the currently active auth token from the header,
       */
      $authSession = $request -> getHeader( 'authorization' );

      /*************************************************
       * Ensure you've downloaded your oauth credentials
       ************************************************/
      if ( $this -> getOAuthCredentialsFile() ) {
        $oauth_credentials = $this -> getOAuthCredentialsFile();
      } else {
        return $response -> withStatus( 500 ) -> write( json_encode( $this -> missingOAuth2CredentialsWarning() ) );
      }

      /************************************************
       * The redirect URI is to the current page, e.g:
       * http://localhost:8080/simple-file-upload.php
       ************************************************/
      $redirect_uri = 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ];
      $client = new Google_Client();
      $client -> setAuthConfig( $oauth_credentials );
      $client -> setRedirectUri( $redirect_uri );
      $client -> addScope( "profile" );
      $service = new Google_Service_Oauth2( $client );

      // add "?logout" to the URL to remove a token from the session
      if ( isset( $_REQUEST[ 'logout' ] ) ) {
        return $response;
      }


      /************************************************
       * If we have a code back from the OAuth 2.0 flow,
       * we need to exchange that with the
       * Google_Client::fetchAccessTokenWithAuthCode()
       * function. We store the resultant access token
       * bundle in the session, and redirect to ourself.
       ************************************************/
      if ( isset( $_GET[ 'code' ] ) ) {
        $token = $client -> fetchAccessTokenWithAuthCode( $_GET[ 'code' ] );
        $client -> setAccessToken( $token );

        // redirect back to the example
        return $response -> withStatus( 401 ) -> withJson( [ "authorization-header" =>  $token ] );
      }


      // set the access token as part of the client
      if ( $response -> hasHeader( 'authorization' ) ) {
        $client -> setAccessToken( $authSession );
        if ( $client -> isAccessTokenExpired() ) {
          return $response -> withStatus( 401 ) -> withJson( [ "error" => "session expired" ] );
        }
      } else {
        $authUrl = $client -> createAuthUrl();
        return $response -> withStatus( 401 ) -> withHeader( 'Location', filter_var( $authUrl, FILTER_SANITIZE_URL ) );
//        return $response -> withStatus( 401 ) -> withJson( ["authUrl" =>  $authUrl] );
      }


      if ( $client -> getAccessToken() ) {
        $response -> write( json_encode( $service -> userinfo -> get() ) );
        $request = $request -> withAttribute( 'user_info', $service -> userinfo -> get() );
        return $next( $request, $response );
      }

    }

    private function getOAuthCredentialsFile()
    {
      // oauth2 creds
      $oauth_creds = './oauth-credentials.json';

      if ( file_exists( $oauth_creds ) ) {
        return $oauth_creds;
      }

      return false;
    }

    private function missingOAuth2CredentialsWarning()
    {
      return [
        "error" => "Warning: You need to set the location of your OAuth2 Client Credentials from the Google API console at http://developers.google.com/console. 
      Once downloaded, move them into the root directory of this repository and rename them 'oauth-credentials.json"
      ];
    }

    private function generateHash()
    {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen( $characters );
      $hashData = '';

      for ( $i = 0; $i < 50; $i++ ) {
        $hashData .= $characters[ rand( 0, $charactersLength - 1 ) ];
      }

      return date( 'Y-m-d H:i:s' ) . $hashData . microtime( true );

    }

    private function cache_set( $key, $val )
    {
      $val = var_export( $val, true );
      // HHVM fails at __set_state, so just use object cast for now
//      $val = str_replace('stdClass::__set_state', '(object)', $val);
      // Write to temp file first to ensure atomicity
      $tmp = "./tmp/$key." . uniqid( '', true ) . '.tmp';
      file_put_contents( $tmp, '<?php $val = ' . $val . ';', LOCK_EX );
      rename( $tmp, "./tmp/$key" );
    }

    function cache_get( $key )
    {
      @include "/tmp/$key";
      return isset( $val ) ? $val : false;
    }

  }

  ?>