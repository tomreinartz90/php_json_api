<?php
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
class PhpJsonApi
{
    private $slim = new \Slim\App();
    private $config;
    /**
     * app constructor.
     */
    public function __construct($config)
    {
        if(isset($config)){
            $this->config = $config;
            $this->setupFromConfig();
        }
    }

    public function run($slim, $config){
        $slim->run();
    }

    private function setupFromConfig($config)
    {
        if(isset($this->config['routes'])){
            $this->setupSlimRoutes(config['routes']);
        }
    }

    private function setupSlimRoutes($routes)
    {
        foreach ($routes as $route) {
            //setup route groups
            if (isset($route['routeGroup'])) {
                $this->slim->group($route['routeGroup'], function () {
                    $this->get('/', function ($request, $response, $args) {
                        return $this->getRequestHandler($request, $response, $args) });
                    $this->get('/{id:[0-9]+}', function ($request, $response, $args) {
                        return $this->getRequestHandler($request, $response, $args) });
                });
            }

            //setup individual routes
            if (isset($route['route'])) {
                $this->slim->map((['GET', 'DELETE', 'PATCH', 'PUT'], $route['route'])
            }
        }
    }

    private function getRequestHandler($config, $request, $response, $args){

    }
}