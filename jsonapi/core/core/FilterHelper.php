<?php

/**
 * Created by PhpStorm.
 * User: taren
 * Date: 8-6-2017
 * Time: 22:55
 */
namespace JsonApi;

class FilterHelper
{

    private $rawQuery;

    /**
     * Array of AND conditions nested with an Array of or conditions
     */
    private $filter = [[]];

    private $FilterTypes = [
        "LESS_THEN" => "<",
        "LESS_THEN_EQUALS" => "<:",
        "GREATER_THEN" => ">",
        "GREATER_THEN_EQUALS" => ">:",
        "EQUALS" => ":",
        "STRICT_EQUALS" => "::",
        "NOT_EQUALS" => "!"
    ];

    private $MedooFilterTypes = [
        "LESS_THEN" => "[<]",
        "LESS_THEN_EQUALS" => "[<=]",
        "GREATER_THEN" => "[>]",
        "GREATER_THEN_EQUALS" => "[>=]",
        "EQUALS" => "[~]",
        "STRICT_EQUALS" => "",
        "NOT_EQUALS" => "[!]"
    ];
    /**
     * FilterHelper constructor.
     */
    public function __construct($requestParams)
    {
        $this->rawQuery = urldecode($requestParams);
        $this->parseQuery();
    }

    /**
     * @return array
     */
    public function getFilter(){
        return $this->filter;
    }

    public function getMeedoFilter($columnConfig){
        $meedooFilterContent = [];
        foreach ($this->getFilter() as $key=>$andCondition){
            $orFilter = [];
            foreach ($andCondition as $filter){
                $columnField = $this->getRealMedooColumn($filter['column'], $columnConfig);
                if($columnField !== null){
                    $orFilter[$columnField . $this->MedooFilterTypes[$filter['filterType']]] = $filter['value'];
                }
            };
            if(count($orFilter) > 0) {
                $meedooFilterContent["OR #".$key] = $orFilter;
            }
        }
        if(count($meedooFilterContent) > 0){
            return ["AND" => $meedooFilterContent];
        }
        return [];
    }

    public static function getRealMedooColumn($columnName, $columnConfig){
        $column = null;
        if($columnConfig){
            foreach ($columnConfig as $columnInfo){
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\)|\s*\[(?<type>(String|Bool|Int|Number|Object|JSON))\])?/i', $columnInfo, $match);
                if($match[ 'column' ] == $columnName || (isset($match['alias']) && $match[ 'alias' ] == $columnName)){
                    $column = $match['column'];
                }
            }
        }
        return $column;
    }

    /**
     * method to parse the queryparams to an array of filter objects.
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

        $this->filter = $filter;
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

