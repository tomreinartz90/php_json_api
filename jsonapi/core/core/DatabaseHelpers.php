<?php
/**
 * Created by PhpStorm.
 * User: Reinartz.T
 * Date: 30-5-2017
 * Time: 13:58
 */

namespace JsonApi;


use Medoo\Medoo;
use JsonApi\FilterHelper;

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

    $filter = new \JsonApi\FilterHelper($query);

    $medooFilter = $filter->getMeedoFilter($columns);
    //count the number of records in the db.
    $count = $this->database->count($table, $medooFilter);
    $totalPages = floor($count / $size);
    $content = null;
    //get data from the db if there is data to get from the db
    if($page <= $totalPages){
      $pageingArray = ["LIMIT" => [($size * $page), $size ]];
      $fullFilter = array_merge($pageingArray, $medooFilter);

      $content = $this->database->select($table, $columns, $fullFilter);
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
    ];


    //return the object
    return $pageable;
  }

  //get details about a record from the db
  function getRecordInTable($table, $columns, $idField, $idFieldValue){
    return $this->database->select($table, $columns, [$idField => $idFieldValue, "LIMIT" => [0, 1]]);
  }

  //update a record in the db
  function updateRecordInTable($table, $columns, $data, $idField, $idFieldValue){
    $parsedData = $this->parseColumnsInCreateObject($data, $columns, $idField);
    $update = $this->database->update($table, $parsedData, [$idField => $idFieldValue]);


    return  $this->handleInsertOrUpdate($update, $table, $columns, $idField);

  }

  //delete a record in the db
  function deleteRecordInTable($table, $idField, $idFieldValue){
    $this->database->delete($table, [$idField => $idFieldValue]);
    return null;
  }

  //add a record in to the db
  function addRecordInTable($table, $columns, $data, $idField){
    $parsedData = $this->parseColumnsInCreateObject($data, $columns, $idField);

    //insert data in the db.
    $insert = $this->database->insert($table, $parsedData);
    return $this->handleInsertOrUpdate($insert, $table, $columns, $idField);

  }

  function error(){
    $error = $this->database->error();
    if($error and $error[2] != null){
      return $error;
    } else {
      return null;
    }
  }

  private function parseColumnsInCreateObject($postBody, $columns, $idField){
    $returnObject = null;
    if(isset($postBody) AND isset($columns)){
      foreach ($postBody as $column=>$value) {
        $returnObject[\JsonApi\FilterHelper::getRealMedooColumn($column, $columns)] = $value;
      }
    }

    //never update the idField in the db;
    unset($returnObject[$idField]);

    return $returnObject;
  }

  private function handleInsertOrUpdate($insetOrUpdate, $table, $columns, $idField){
    if($insetOrUpdate->rowCount() === 0){
      return $this->error();
    } else {
      //get the record that has been created.
      return $this->getRecordInTable($table, $columns, $idField, $this->database->id())[0];
    }
  }
}