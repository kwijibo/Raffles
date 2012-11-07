<?php

namespace Raffles;

class StreamingTurtleParser extends \ARC2_TurtleParser {

  var $callback;

  function __construct(){
    parent::__construct(array(), new \StdClass());
  }
  
  function setCallback(&$callback){
    $this->callback = $callback;
  }

  function addT($t){
    if($this->t_count > 5000){
      call_user_func($this->callback, $this->getSimpleIndex(0));
      $this->triples = array();
      $this->t_count = 0;
    }
    parent::addT($t);
  }

}

class StreamingRDFXMLParser extends \ARC2_RDFXMLParser {

  var $callback;

    function __construct(){
    parent::__construct(array(), new \StdClass());
  }

  function setCallback(&$callback){
    $this->callback = $callback;
  }

  function addT($s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = ''){
    if($this->t_count > 1000){
      call_user_func($this->callback, $this->getSimpleIndex(0));
      $this->triples = array();
      $this->t_count = 0;
    }
    parent::addT($s, $p, $o, $s_type, $o_type, $o_dt, $o_lang);
  }

}

?>
