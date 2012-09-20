<?php 

class Index {

  var $index = array(
    'subjects' => array(),
    'po' => array(),
  );
  function __construct(){
  }

  function addTerm($termType, $t, $linenumbers){
    $linenumbers = (array) $linenumbers;
    $t = (string)$t;
    if(!isset($this->index[$termType][$t])) $this->index[$termType][$t] = array();
    $existingLineNumbers = $this->index[$termType][$t];
    foreach($linenumbers as $linenumber){
      if(!in_array($linenumber, $existingLineNumbers)){ 
        array_push( $this->index[$termType][$t], $linenumber );
      }
    }
    return $this->index[$termType][$t];
  }
  
  function addSubject($s, $ln){ return $this->addTerm('subjects', $s, $ln); }

  function replaceSubject($s, $lns){
    $lns = (array)$lns;
    $this->index['subjects'][$s] = $lns;
    return $lns;
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

  function getTerm($termType, $s){
    return $this->index[$termType][$s];  
  }

  function getPredicateObject($p,$o){
      return $this->index['po'][$p][$o];    
  }

  function getSubject($s){ return $this->getTerm('subjects', $s); }
    
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

}

?>
