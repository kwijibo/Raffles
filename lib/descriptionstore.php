<?php

class DescriptionStore {

  var $File;
  function __construct($file){
    $this->File = new SplFileObject($file);
  }

  function insertDescription($description){
    $this->File->fwrite(serialize($description)."\n");
    return $this->File->key();
  }

}

?>
