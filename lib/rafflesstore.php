<?php
require 'index.php';
require 'descriptionstore.php';

class RafflesStore {
  var $Index;
  var $DescriptionStore;
  var $dirname;
  var $indexPredicates = array('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');

  function __construct($dirname){
    $this->dirname = $dirname;
    if(!is_dir($dirname)) mkdir($dirname);
    $index_file = $this->dirname . DIRECTORY_SEPARATOR .'index';
    $this->Index = new Index();
    if(file_exists($index_file)){
      $index = unserialize(file_get_contents($index_file));
      if(is_object($index)){
        $this->Index = $index;
      }
    }
    $this->DescriptionStore = new DescriptionStore($dirname . DIRECTORY_SEPARATOR . 'descriptions');
  }

  function load($descriptions){
    $count = array(
      's' => 0,
      'p' => 0,
      'o' => 0
    );
    foreach($descriptions as $s => $props){
      $lineNumber = $this->DescriptionStore->insertDescription(array($s => $props));
      $this->Index->addSubject($s, $lineNumber);
      $count['s']++;
      foreach($props as $p => $objs){
        $count['p']++;
        foreach($objs as $obj){
          if(!$this->indexPredicates OR in_array($p, $this->indexPredicates)) $this->Index->addPredicateObject($p, $obj['value'], $lineNumber);
          $count['o']++;
        }
      }
    }
    return $count;
  }

  function get($s=false, $p=false, $o=false,$limit=50,$offset=0){
    $descriptions = array();

    if($s && $p && $o) $IDs = array_merge($this->Index->getSubject($s), $this->Index->getPredicateObject($p, $o));
    if($s && $p && !$o) $IDs = array_merge($this->Index->getSubject($s), $this->Index->getPredicate($p));
    if($s && !$p && $o) $IDs = array_merge($this->Index->getSubject($s), $this->Index->getObject($o));
    if($s && !$p && !$o) $IDs = $this->Index->getSubject($s);
    if(!$s && $p && $o) $IDs = $this->Index->getPredicateObject($p, $o);
    if(!$s && !$p && $o) $IDs = $this->Index->getObject($o);
    if(!$s && $p && !$o) $IDs = $this->Index->getPredicate($p);
    if(!$s && !$p && !$o) $IDs = range(0, $this->DescriptionStore->size());
    
    return $this->DescriptionStore->getDescriptionsByIDs(array_slice($IDs, $offset, $limit));
  }

  function loadData($data){
    $graph = new EasyRdf_Graph();
    $graph->parse($data);
    return $this->load($graph->toArray());
  }

  function getFacets($p){
    $propertyIndex = $this->Index->getPredicateValues($p);
    foreach($propertyIndex as $o => $lns){
      $propertyIndex[$o] = count($lns);
    }
    return $propertyIndex;
  }

  function search($o_text, $property=false){
    $ids = $this->Index->searchObject($o_text,$property);
    return $this->DescriptionStore->getDescriptionsByIDs($ids);
  }

  function __destruct() {
    file_put_contents($this->dirname . DIRECTORY_SEPARATOR .'index', serialize($this->Index));
  }

  function reset(){
    foreach(array('index','descriptions') as $filename){
      $full_filepath = $this->dirname . DIRECTORY_SEPARATOR .$filename;
      if(is_file($full_filepath)) unlink($full_filepath);
    }
    $this->Index = new Index();
    $this->DescriptionStore = new DescriptionStore($this->dirname . DIRECTORY_SEPARATOR . 'descriptions');
  }
}
