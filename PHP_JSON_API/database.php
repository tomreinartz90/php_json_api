<?php
/**
 * Created by PhpStorm.
 * User: Reinartz.T
 * Date: 30-5-2017
 * Time: 13:58
 */
require_once "FilterHelper.php";

use Medoo\Medoo;

class DatabaseHelpers
{


  private $database;

  function __construct($dbConfig)
  {
    $this->database = new Medoo($dbConfig);
  }

  //get a pagable object from the db
  function getRecordsInTable($table, $columns, $page, $size, $query){
    $page = intval($page);
    $size = intval($size);

    $filter = new FilterHelper($query);

    //count the number of records in the db.
    $count = $this->database->count($table, []);
    $totalPages = floor($count / $size);

    //get data from the db if there is data to get from the db
    if($page <= $totalPages){
      $content = $this->database->select($table, $columns, ["LIMIT" => [($size * $page), $size ]]);
    }

    //create a pageable object
    $pageable = [
      'content' => isset($content) ? $content : [],
      'firstPage' => $page == 0,
      'lastPage' => $page == $totalPages,
      'page' => $page,
      'size' => $size,
      'totalPages' => $totalPages,
      'totalElements' => $count,
      'filter' => $filter->filter
    ];


    //return the object
    return $pageable;
  }

  //get details about a record from the db
  function getRecordInTable($table, $columns, $idField, $idFieldValue){
    return $this->database->select($table, $columns, [$idField => $idFieldValue, "LIMIT" => [0, 1]]);
  }

  //update a record in the db
  function updateRecordInTable($table, $data, $idField, $idFieldValue){
    return $this->database->update($table, $data, [$idField => $idFieldValue]);
  }

  //delete a record in the db
  function deleteRecordInTable($table, $idField, $idFieldValue){
    return $this->database->delete($table, [$idField => $idFieldValue]);
  }

  //add a record in to the db
  function addRecordInTable($table, $data){
    return $this->database->insert($table, $data);
  }

  function error(){
    $error = $this->database->error();
    if($error and $error[2] != null){
      return $error;
    } else {
      return null;
    }
  }
}