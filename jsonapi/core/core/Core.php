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
        $dynamicMiddleWare = function($request, $response, $next) use ($app){
          $routeInfo = $request->getAttribute('route_info'); //get the session from the request
          if(isset($routeInfo['middleWare'])){
            return $routeInfo['middleWare']($request, $response, $next);
          } else {
            return $next($request, $response);
          }
        };


        if (isset($route['routeGroup'])) {
          /**
           * create a new routegroup per config provided
           */
          $this->slim->group($route['routeGroup'], function () use ($route, $app)  {

            /**
             * create new endpoints to get a collection of data and create a new object
             */
            $this->map(['GET', 'POST'], '', function ($request, $response, $args) use ($app, $route) {
              $request = $request->withAttribute('route_info', $route); //add the session storage to your request as [READ-ONLY]
              return $app->dataBaseMiddleWare($request, $response, function(){});
            })->setName($route['routeGroup']);

            /**
             * create new endpoints to GET, PUT, DELETE, specific information,
             */
            $this->map(['GET', 'DELETE', 'PATCH', 'PUT'], '/{id:[0-9]+}', function ($request, $response, $args) use ($app, $route) {
              $request = $request->withAttribute('route_info', $route); //add the session storage to your request as [READ-ONLY]
              return $app->dataBaseMiddleWare($request, $response, function(){});
            })->setName($route['routeGroup'] . '-details');


          })->add($dynamicMiddleWare);
        }

        //setup individual routes
        if (isset($route['route'])) {
          $this->slim->map(['GET', 'DELETE', 'PATCH', 'PUT'], $route['route'], function ($request, $response, $args) use ($route, $app) {
            $request = $request->withAttribute('route_info', $route); //add the session storage to your request as [READ-ONLY]
            return $app->dataBaseMiddleWare($request, $response, function(){});
          })->add($dynamicMiddleWare);
        }
      }
    }



    protected function dataBaseMiddleWare( $request, $response, $next){
      //get the route info (for the db)
      $routeInfo = $request->getAttribute('route_info');

      //get the session from the request
      $args = $request->getAttribute('routeInfo')[2];
//    var_dump($routeInfo);
      $showDetails = isset($args['id']);
      $idValue = $showDetails ? $args['id'] : null;
      $database = new \JsonApi\DatabaseHelpers($this->config['database_config']);
      $returnData = null;
      $notFound = false;

      $next($request, $response);

      if(is_null($routeInfo) ){
        return $response->withStatus(500)->write("invalid routeinfo for Core::dataBaseMiddleWare");
      }


      //handle get request
      if($request->isGet()){
        $params = $request->getQueryParams();
        $query = $request->getUri()->getQuery();
        if($showDetails){
          $returnData = $database->getRecordInTable($routeInfo['table'], $routeInfo['columns'], $routeInfo['idField'], $idValue);
          //db returns list of data, only 1 record is needed.
          if(isset($returnData[0])){
            $returnData = $returnData[0];
          } else {
            $notFound = true;
          }
        } else {
          $page = isset($params['page']) ? $params['page'] : 0;
          $size = isset($params['size']) ? $params['size'] : (isset( $this->config['default_page_size'] ) ? $this->config['default_page_size'] : 20);
          $returnData = $database->getRecordsInTable($routeInfo['table'], $routeInfo['columns'],  $page, $size, $query);
        }
      }

      //hanlde put request
      if($request->isPut()){
        $parsedBody = $request->getParsedBody();
        $returnData = $database->updateRecordInTable($routeInfo['table'], $routeInfo['columns'], $parsedBody, $routeInfo['idField'], $idValue);
      }

      //handle post request
      if($request->isPost()){
        $parsedBody = $request->getBody();
        $returnData = $database->addRecordInTable($routeInfo['table'], $routeInfo['columns'], $parsedBody, $routeInfo['idField']);
      }

      //handle delete request
      if($request->isDelete()){
        $returnData = $database->deleteRecordInTable($routeInfo['table'], $routeInfo['idField'], $idValue);
      }
      $response =  $response->withHeader('Content-Type', 'application/json');
      if( !$database->error() ){
        //check if there was data found
        $response = $response->withStatus($notFound ? 404 : 200)->write(json_encode($returnData));
      } else {
        $errorObj = [
          'message' => $database->error()[2]
        ];
        $response = $response->withStatus(500)->write(json_encode($errorObj));
      }

      //goto the next middleware
//    return $next($request, $response);
      return $response;
    }
  }