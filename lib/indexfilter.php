<?php
namespace Raffles;

class IndexFilter{
  
  var $Index, $ids; 
  function __construct(&$index, $ids=null){
    $this->Index = $index;
    $this->ids = $ids;
  }

  function filter($s=null, $p=null,$o=null){
    $IDs = $this->match($s, $p, $o);
    if(is_array($this->ids)){
      $IDs = array_values(array_intersect($this->ids, $IDs));
    }
    $next = new IndexFilter($this->Index, $IDs);
    return $next;
  }


  function match($s=null, $p=null,$o=null){
    if($s && $p && $o) $IDs = array_intersect(array($this->Index->getSubject($s)), $this->Index->getPredicateObject($p, $o));
    if($s && $p && !$o) $IDs = array_intersect(array($this->Index->getSubject($s)), $this->Index->getPredicate($p));
    if($s && !$p && $o) $IDs = array_intersect(array($this->Index->getSubject($s)), $this->Index->getObject($o));
    if($s && !$p && !$o) $IDs = $this->Index->getSubject($s);
    if(!$s && $p && $o) $IDs = $this->Index->getPredicateObject($p, $o);
    if(!$s && !$p && $o) $IDs = $this->Index->getObject($o);
    if(!$s && $p && !$o) $IDs = $this->Index->getPredicate($p);
    if(!$s && !$p && !$o) $IDs = $this->Index->getAll();
    return $IDs;
  }
  function ids(){
    return $this->ids;
  }

  function traverseOut($predicate){
    $subject_index_ids = array();
    foreach($this->ids() as $object_index_id){
      $object_uri_value = $this->Index->getSubjectByID($object_index_id);
      $subject_index_ids = array_merge($subject_index_ids, $this->match(null, $predicate, $object_uri_value));
    }
     return new IndexFilter($this->Index, $subject_index_ids);
  }

}

?>
