<?php
/**
 * config needed for the application to run
 */

/**
 *
 * $config->['database_config']
 * see https://medoo.in/
 *
 * $config -> default_page_size {set the default size to fetch from the db.}
 *
 * $config->['routes'][0]
 * route-> routeGroup {explains the app what needs to be mapped by this handler.}
 * this will automaticly handle the request
 * GET, POST on /routeGroup
 * GET, PUT, DELETE on /routeGroup/{id:int}
 *
 * $route-> table { explains where to get the data from in the databse }
 *
 * $route-> columns { explains what columns will be selected when GET request is executed }
 * [columnA, columnB, columnD] {select columns as is}
 * [columnA(name), columnB(age), columnD(gender)] {select column as alias)
 *
 * $route-> idField {column that is unique and will be used to show data of a single row.
 *
 * $route-> requestHandler : function ($request, $response, $args)
 * allows the use of a custom request handler.
 *
 *
 */

$config = [

  "database_config" => [
    'database_type' => 'mysql',
    'database_name' => 'MyVideos107',
    'server' => 'nas.tomreinartz.com',
    'username' => 'xbmc',
    'password' => 'xbmc',
    'logging' => true,
  ],

  "default_page_size" =>  20,

  "routes" => [
    [
      'routeGroup' => '/episodes',
      'table' => 'episode',
      'idField' => 'idEpisode',
      'columns' => ['idEpisode',
        'c00(episodeTitle)',
        'c01(plotSummary)',
        'c02(votes)',
        'c03(rating)',
        'c04(writer)',
        'c05(firstAired)',
        'c06(thumbnailURL)',
        'c08(watched)',
        'c09(episodeLength)',
        'c10(director)',
        'c12(season)',
        'c13(episode)',
        'c15(seasonFormatted)',
        'c16(episodeFormatted)',
        'c17(bookmark)',
      ],
//      'requestHandler' => function($request, $response, $args){
//        echo "custom request handler";
//      }
    ]
  ]

]
?>
