<?php
use Slim\App;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
include_once "database.php";
/**
 * Created by PhpStorm.
 * User: Reinartz.T
 * Date: 30-5-2017
 * Time: 14:39
 */
class PhpJsonApi
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

  private function setupFromConfig()
  {
    if(isset($this->config['routes'])){
      $this->setupSlimRoutes($this->config['routes']);
    }
  }

  private function setupSlimRoutes($routes)
  {
    foreach ($routes as $route) {
      //setup route groups
      if (isset($route['routeGroup'])) {
        $app = $this;
        $this->slim->group($route['routeGroup'], function () use ($route, $app)  {
          $this->map(['GET', 'POST'], '', function ($request, $response, $args) use ($route, $app) {
            //todo add auth check here
            return $app->requestHandler($route, $request, $response, $args);
          })->setName($route['routeGroup']);
          $this->map(['GET', 'DELETE', 'PATCH', 'PUT'], '/{id:[0-9]+}', function ($request, $response, $args) use ($route, $app) {
            //todo add auth check here
            return $app->requestHandler($route, $request, $response, $args);
          })->setName($route['routeGroup'] . '-details');;
        });
      }

      //setup individual routes
      if (isset($route['route'])) {
        $this->slim->map(['GET', 'DELETE', 'PATCH', 'PUT'], $route['route']);
      }
    }
  }



  private function requestHandler($config, $request, $response, $args){
    //todo add auth check here

    if(isset($config['requestHandler'])){
      return $config['requestHandler']($request, $response, $args);
    } else {
      $showDetails = isset($args['id']);
      $idValue = $showDetails ? $args['id'] : null;
      $database = new DatabaseHelpers($this->config['database_config']);
      $returnData = null;
      $notFound = false;
      //handle get request
      if($request->isGet()){
        $params = $request->getQueryParams();
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
          $returnData = $database->getRecordsInTable($config['table'], $config['columns'],  $page, $size);
        }
      }

      //hanlde put request
      if($request->isPut()){

      }

      //handle post request
      if($request->isPost()){

      }

      //handle post request
      if($request->isDelete()){

      }
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
}