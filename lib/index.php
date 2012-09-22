<?php 
require 'indexfilter.php';
class Index {

  var $index = array(
    'subjects' => array(),
    'po' => array(),
  );
  function __construct(){
  }

  function addSubject($s, $linenumber){
    $this->index['subjects'][$s] = $linenumber;
    return $linenumber;
  }
  

  function replaceSubject($s, $ln){
    $this->index['subjects'][$s] = $ln;
    return $ln;
  }
  
  function addPredicateObject($p,$o, $linenumbers){
    if(!is_array($linenumbers)) $linenumbers = array($linenumbers);
    if(!isset($this->index['po'][$p])) $this->index['po'][$p] = array();
    if(!isset($this->index['po'][$p][$o])) $this->index['po'][$p][$o] = array();
    $existingLineNumbers = $this->index['po'][$p][$o];
      foreach($linenumbers as $linenumber){
        if(!in_array($linenumber, $existingLineNumbers)){ 
          array_push( $this->index['po'][$p][$o], $linenumber );
        }
    }
    return $this->index['po'][$p][$o];

  }

  function getPredicateObject($p,$o){
    if(!empty($this->index['po'][$p]) 
      AND !empty($this->index['po'][$p][$o])
    ) { return $this->index['po'][$p][$o]; }
      else { return array();   }
  }

  function getSubject($s){ 
    return $this->index['subjects'][$s];  
  }

  function getSubjectByID($id){
    return array_search($id, $this->index['subjects']);
  }
    
  function getObject($o){ 
    $all_line_numbers=array();
    foreach($this->index['po'] as $p => $os){
      if($os[$o]){
        $all_line_numbers = array_merge($os[$o]);
      }
    }
    return $all_line_numbers;
  }
  
  function getPredicate($p){
      $all_line_numbers = array();
      foreach($this->index['po'][$p] as $o => $line_numbers){
        $all_line_numbers = array_merge($line_numbers, $all_line_numbers);
      }
      return $all_line_numbers;
  }

  function getPredicateValues($p){
    if(!isset($this->index['po'][$p])) return array();
    return $this->index['po'][$p];
  }

  function searchObject( $o, $p=false){
    $all_ids = array();
   if(empty($p)){
      $p = array_keys($this->index['po']);
    }
    $p = (array)$p;
    foreach($p as $no => $property){
      foreach($this->index['po'][$property] as $object => $ids){
        if(stripos($object,$o)!==false){
          $all_ids = array_merge($all_ids, $ids);
        }
      }
    }
    return $all_ids;
  }


  function getAll(){
    $ids = array_values($this->index['subjects']);
    return $ids;
  }

  function filter($s=null, $p=null,$o=null){
    $filter = new IndexFilter($this);
    return $filter->filter($s, $p, $o);
  }

  function query($triples){
    $filter = new IndexFilter($this);
    $triple = array_shift($triples);
    $s = ($triple['s']['type']=='variable')? null : $triple['s']['value'];
    $p = ($triple['p']['type']=='variable')? null : $triple['p']['value'];
    $o = ($triple['o']['type']=='variable')? null : $triple['o']['value'];
    $filter = $filter->filter($s,$p, $o);
    foreach($triples as $triple){
      $p = ($triple['p']['type']=='variable')? null : $triple['p']['value'];
      $filter = $filter->traverseOut($p);
    }
    return $filter->ids();
  }
}

?>
