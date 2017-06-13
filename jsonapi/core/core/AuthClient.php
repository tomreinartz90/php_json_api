<?php

/**
 * Created by PhpStorm.
 * User: taren
 * Date: 11-6-2017
 * Time: 14:00
 */
namespace JsonApi;
use \JsonApi\DatabaseHelpers;

class AuthClient {
  /**
   * Auth constructor.
   */
  private $config;
  private $authUrl;
  public function __construct($config = [], $authUrl)
  {
    $this->config = $config;
    $this->authUrl = $authUrl;
  }

  public function __invoke($request, $response, $next)
  {

    $token = $request->getHeader('X-AUTH-TOKEN');

    if($this->authUrl == null OR $this->config['authorize'] == false OR $this->config['authorize'] == null OR ( $token AND $this->isAuthorized($token) )){
      $response = $next($request, $response);
    } else {
      $response->getBody()->write("{message: 'you are not allowed to access this resource.'}");
      $response = $response->withHeader('Content-Type', 'application/json');
      $response = $response->withStatus(401);
    }

    return $response;
  }

  private function isAuthorized($token)
  {
    $request = new HttpRequest($this->authUrl . '/is-authorized/' . $token, HttpRequest::METH_GET);
    try {
      $request->send();
      return $request->getResponseBody();
    } catch (HttpException $exception) {
      return false;
    }
  }
}