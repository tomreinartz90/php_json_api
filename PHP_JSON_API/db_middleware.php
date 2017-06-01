<?php

/**
 * Created by PhpStorm.
 * User: Reinartz.T
 * Date: 30-5-2017
 * Time: 14:18
 */
class db_middleware
{
    public function __invoke($request, $response, $next)
    {
        $response->getBody()->write('BEFORE');
        $response = $next($request, $response);
        $response->getBody()->write('AFTER');

        return $response;
    }
}