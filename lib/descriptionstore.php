<?php

class DescriptionStore {

  var $File, $filename;
  function __construct($filename){
    $this->filename = $filename;
  }

  function size(){
    $i = 0;
    $file = fopen($this->filename, 'a+');
    rewind($file);
    while($line=fgets($file)){
            $i++;
    }
    fclose($file);
    return $i;
  }

  function insertDescription($description){
    $contents = json_encode($description)."\n";
    file_put_contents($this->filename, $contents, FILE_APPEND|LOCK_EX);
    return ($this->size()-1);
  }

  function getDescriptionsByIDs($numbers){
    $numbers = (array) $numbers;
    $descriptions = array();
    if(empty($numbers)) return $descriptions;
    $i = 0;
    $file = fopen($this->filename, 'r');
    rewind($file);
    $lastLine = max($numbers);
    while($line=fgets($file) AND $i <= $lastLine){
      if(in_array($i, $numbers)){
        $descriptions = array_merge($descriptions, json_decode($line, 1));
      }
      $i++;
    }
    fclose($file);
    return $descriptions;
  }

  function reset(){
    file_put_contents($this->filename, '');
  }

}

?>
