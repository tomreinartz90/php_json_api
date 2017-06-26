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
    ],
    [
      'routeGroup' => '/tvshows',
      'table' => 'tvshow',
      'idField' => 'idShow',
      'columns' => ['idShow',
        'c00(showTitle)',
        'c01(plotSummary)',
        'c02(status)',
        'c03(votes)',
        'c04(rating)',
        'c05(firstAired)',
        'c06(thumbnailURL)',
        'c08(genre)',
        'c09(originalTitle)',
        'c13(contentRating)',
        'c14(network)',
        'c15(sortTitle)'
      ],
    ],
    [
      'routeGroup' => '/files',
      'table' => 'files',
      'idField' => 'idFile',
      'columns' => ['idFile',
        'idPath(idPath)',
        'strFilename(fileName)',
        'playCount(played)',
        'lastPlayed(lastPlayed)',
        'dateAdded(added)',
      ],
    ],
    [
      'routeGroup' => '/paths',
      'table' => 'path',
      'idField' => 'idPath',
      'columns' => ['idPath',
        'strPath(path)',
        'strContent(contains)',
        'strScraper(scraper)',
        'strHash(hash)',
      ],
    ],
    [
      'routeGroup' => '/movies',
      'table' => 'movie',
      'idField' => 'idMovie',
      'columns' => ['idMovie',
        'c00(title)',
        'c01(plot)',
        'c02(plotOutline)',
        'c03(tagline)',
        'c04(votes)',
        'c05(rating)',
        'c06(writers)',
        'c07(year)',
        'c08(thumbnails)',
        'c09(imdbId)',
        'c10(sortTitle)',
        'c11(runTime)',
        'c14(genre)',
        'c15(director)',
        'c16(originalTitle)',
        'c18(studio)',
        'c19(trailer)',
        'c20(fanArt)',
        'c20(country)',
        'c20(fileId)',
        'c20(setId)',
      ],
    ],

  ]

]
?>
