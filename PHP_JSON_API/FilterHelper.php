<?php

/**
 * Created by PhpStorm.
 * User: taren
 * Date: 8-6-2017
 * Time: 22:55
 */
class FilterHelper
{

  private $rawQuery;
  public $query;

  private $FilterTypes = [
    "LESS_THEN" => "<",
    "LESS_THEN_EQUALS" => "<:",
    "GREATER_THEN" => ">",
    "GREATER_THEN_EQUALS" => ">:",
    "EQUALS" => ":",
    "STRICT_EQUALS" => "::",
    "NOT_EQUALS" => "!"
  ];
  /**
   * FilterHelper constructor.
   */
  public function __construct($requestParams)
  {
    $this->rawQuery = urldecode($requestParams);
    $this->parseQuery();
  }

  public function getFilter(){

  }

  /**
   * method to parse the queryparams to an array of filter objects.
   *
   *
   */
  private function parseQuery()
  {
    //get filter parts from filter
    $pieces = explode("&", $this->rawQuery);
    $filterParts = [];
    foreach ($pieces as $piece){
      if (0 === strpos($piece, 'filter=')) {
        //add option for OR filter
        array_push($filterParts, explode("||", substr($piece, 7) ) );
      }
    }

    $filter = [];
    foreach ($filterParts as $andCondition){
      $andFilter = [];
      foreach ($andCondition as $orCondition){
        $filterType = $this->getFilterType($orCondition);
        $columnValue = preg_split("/[:><!]+/", $orCondition, 2);
        if($filterType AND  count($columnValue) == 2){
          array_push($andFilter, [
            "filterType" => $filterType,
            "column" =>  $columnValue[0],
            "value" => $columnValue[1]
          ]);
        }
      }
      array_push($filter, $andFilter);
    }

    $this->query = $filter;
  }


  /**
   * get the filterType from the condition string
   * @param $filterString
   * @return bool|string
   */
  private function getFilterType($filterString){
    if(strpos($filterString, '::')) {
      return "STRICT_EQUALS";
    }
    if(strpos($filterString, '!:')) {
      return "NOT_EQUALS";
    }
    if(strpos($filterString, '<:')) {
      return "LESS_THEN_EQUALS";
    }
    if(strpos($filterString, '>:')) {
      return "GREATER_THEN_EQUALS";
    }
    if(strpos($filterString, ':')) {
      return "EQUALS";
    }
    if(strpos($filterString, '>')) {
      return "GREATER_THEN";
    }
    if(strpos($filterString, '<')) {
      return "LESS_THEN";
    }
    return false;

  }
}

