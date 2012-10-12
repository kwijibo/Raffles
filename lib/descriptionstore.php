<?php
namespace Raffles;
class DescriptionStore {

  var $File, $filename;
  function __construct($filename){
    $this->filename = $filename;
    if(!is_file($filename)){
      touch($filename);
    }
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
    $lock = flock($fh, LOCK_EX);
    if(!$lock){
      throw new Exception("Couldn't get lock on file: $this->filename for inserting descriptions");
    }
    $id_no = $this->size();
    $uris_to_ids = array();
    foreach($descriptions as $uri => $props){
      $contents = json_encode(array($uri => $props))."\n";
      $uris_to_ids[$uri] = $id_no++;
      fwrite($fh, $contents);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return $uris_to_ids;
  }

    function replaceDescriptions($ids_to_descriptions){
    $tmp = tempnam('/tmp','Raffles_');
    $temp = fopen($tmp, 'w');
    $fh = fopen($this->filename, 'r');
    rewind($fh);
    $lock = flock($fh, LOCK_SH|LOCK_NB);
    if(!$lock){
      throw new Exception("Couldn't get lock on file: $this->filename reading descriptions");
    }
    $i = 0;
    while($line = fgets($fh)){
      if(isset($ids_to_descriptions[$i])){
        $line = json_encode($ids_to_descriptions[$i])."\n";
      }
      fwrite($temp, $line);
      $i++;
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    unlink($this->filename);
    rename($tmp,$this->filename);
  }


  function getDescriptionsByIDs($numbers){
    $numbers = (array) $numbers;
    $descriptions = array();
    if(empty($numbers)) return $descriptions;
    $i = 0;
    $file = fopen($this->filename, 'r');
    flock($file,LOCK_SH);
    rewind($file);
    $lastLine = max($numbers);
    while($line=fgets($file) AND $i <= $lastLine){
      if(in_array((string)$i, $numbers)){
        $descriptions[$i] =  json_decode($line, 1);
      }
      $i++;
    }
    flock($file,LOCK_UN);
    fclose($file);
    $sorted= array();
    foreach($numbers as $num){
      if(isset($descriptions[$num])){
        $sorted = array_merge($sorted, $descriptions[$num]);
      }
    }
    return $sorted;
  }

  function reset(){
    file_put_contents($this->filename, '');
  }

}

?>
