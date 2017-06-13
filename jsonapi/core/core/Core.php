<?php
namespace JsonApi;

use JsonApi\DatabaseHelpers;
use Slim\App;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
/**
 * Created by PhpStorm.
 * User: Reinartz.T
 * Date: 30-5-2017
 * Time: 14:39
 */

class Core
{
  public $slim;
  private $config;
  /**
   * app constructor.
   */
  public function __construct($config)
  {

    $this->slim =  new \Slim\App(['displayErrorDetails' => true]);
    if(isset($config)){
      $this->config = $config;
      $this->setupFromConfig();
    }
  }

  public function run(){
    $this->slim->run();
  }

  protected function setupFromConfig()
  {
    if(isset($this->config['routes'])){
      $this->setupSlimRoutes($this->config['routes']);
    }
  }

  protected function setupSlimRoutes($routes)
  {
    foreach ($routes as $route) {
      //setup route groups
      $app = $this;
      $auth = new \JsonApi\AuthClient($route, isset($config['auth_url']) ? $config['auth_url'] : null);
      if (isset($route['routeGroup'])) {
//        var_dump( $route );
        $this->slim->group($route['routeGroup'], function () use ($route, $app)  {
          $this->map(['GET', 'POST'], '', function ($request, $response, $args) use ($route, $app) {
            //todo add auth check here
            if(isset($config['requestHandler'])){
              return $config['requestHandler']($request, $response, $args);
            } else {
              return $app->requestHandler($route, $request, $response, $args);
            }          })->setName($route['routeGroup']);
          $this->map(['GET', 'DELETE', 'PATCH', 'PUT'], '/{id:[0-9]+}', function ($request, $response, $args) use ($route, $app) {
            if(isset($config['requestHandler'])){
              return $config['requestHandler']($request, $response, $args);
            } else {
              return $app->requestHandler($route, $request, $response, $args);
            }
          })->setName($route['routeGroup'] . '-details');
        })->add($auth);
      }

      //setup individual routes
      if (isset($route['route'])) {
        $this->slim->map(['GET', 'DELETE', 'PATCH', 'PUT'], $route['route'], function ($request, $response, $args) use ($route, $app) {
          return $app->requestHandler($route, $request, $response, $args);
        })->add(function($request, $response, $next){
          if(isset($route['middleWare'])){
            return $route['middleWare']($request, $response, $next);
          } else {
            return $response = $next($request, $response);

          }
        })->add($auth);
      }
    }
  }



  protected function requestHandler($config, $request, $response, $args){
    $showDetails = isset($args['id']);
    $idValue = $showDetails ? $args['id'] : null;
    $database = new \JsonApi\DatabaseHelpers($this->config['database_config']);
    $returnData = null;
    $notFound = false;
    //handle get request
    if($request->isGet()){
      $params = $request->getQueryParams();
      $query = $request->getUri()->getQuery();
      if($showDetails){
        $returnData = $database->getRecordInTable($config['table'], $config['columns'], $config['idField'], $idValue);
        //db returns list of data, only 1 record is needed.
        if(isset($returnData[0])){
          $returnData = $returnData[0];
        } else {
          $notFound = true;
        }
      } else {
        $page = isset($params['page']) ? $params['page'] : 0;
        $size = isset($params['size']) ? $params['size'] : (isset( $this->config['default_page_size'] ) ? $this->config['default_page_size'] : 20);
        $returnData = $database->getRecordsInTable($config['table'], $config['columns'],  $page, $size, $query);
      }
    }

    //hanlde put request
    if($request->isPut()){
      $parsedBody = $request->getParsedBody();
      $returnData = $database->updateRecordInTable($config['table'], $config['columns'], $parsedBody, $config['idField'], $idValue);
    }

    //handle post request
    if($request->isPost()){
      $parsedBody = $request->getBody();
      $returnData = $database->addRecordInTable($config['table'], $config['columns'], $parsedBody, $config['idField']);
    }

    //handle delete request
    if($request->isDelete()){
      $returnData = $database->deleteRecordInTable($config['table'], $config['idField'], $idValue);
    }
    $response =  $response->withHeader('Content-Type', 'application/json');
    if( !$database->error() ){
      //check if there was data found
      return $response->withStatus($notFound ? 404 : 200)->write(json_encode($returnData));
    } else {
      $errorObj = [
        'message' => $database->error()[2]
      ];
      return $response->withStatus(500)->write(json_encode($errorObj));

    }

  }
}