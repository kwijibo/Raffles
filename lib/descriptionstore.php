<?php

class DescriptionStore {

  var $File, $filename;
  function __construct($filename){
    $this->filename = $filename;
  }

  function size(){
    $i = 0;
    $file = fopen($this->filename, 'r');
    rewind($file);
    while($line=fgets($file)){
            $i++;
    }
    fclose($file);
    return $i;
  }

  function insertDescriptions($descriptions){
    $fh = fopen($this->filename, 'a+');
    $id_no = $this->size();
    $uris_to_ids = array();
    foreach($descriptions as $uri => $props){
      $contents = json_encode(array($uri => $props))."\n";
      $uris_to_ids[$uri] = $id_no++;
      fwrite($fh, $contents);
    }
    fclose($fh);
    return $uris_to_ids;
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
