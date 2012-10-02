<?php

require_once 'arc/ARC2.php';
require_once 'arc/parsers/ARC2_TurtleParser.php';
require_once 'arc/parsers/ARC2_RDFXMLParser.php';


function merge(){
  $old_bnodeids = array();
  $indexes = func_get_args();
  $current = array_shift($indexes);
  foreach($indexes as $newGraph)
  {
    foreach($newGraph as $uri => $properties)
    {
      /* Make sure that bnode ids don't overlap: 
      _:a in g1 isn't the same as _:a in g2 */

      if(substr($uri,0,2)=='_:')//bnode
      {
        $old_id = $uri;
        $count = 1;

        while(isset($current[$uri]) OR 
        ( $old_id!=$uri AND isset($newGraph[$uri]) )
        OR isset($old_bnodeids[$uri])
        )
        {
          $uri.=$count++;
        }

        if($old_id != $uri)	$old_bnodeids[$old_id] = $uri;
      }

      foreach($properties as $property => $objects)
      {
        foreach($objects as $object)
        {
          /* make sure that the new bnode is being used*/
          if($object['type']=='bnode')
          {
            $bnode = $object['value'];

            if(isset($old_bnodeids[$bnode])) $object['value'] = $old_bnodeids[$bnode];
            else //bnode hasn't been transposed
            {
                $old_bnode_id = $bnode;
                $count=1;
                while(isset($current[$bnode]) OR 
                ( $object['value']!=$bnode AND isset($newGraph[$bnode]) )
                OR isset($old_bnodeids[$uri])
                )
                {
                  $bnode.=$count++;
                }

                if($old_bnode_id!=$bnode)	$old_bnodeids[$old_bnode_id] = $bnode;
                $object['value'] = $bnode;
            }
          }

          if(!isset($current[$uri][$property]) OR !in_array($object, $current[$uri][$property]))
          {
            $current[$uri][$property][]=$object;
          }
        }
      }

    }
  }
  return $current;
}


class StreamingTurtleParser extends ARC2_TurtleParser {

  var $callback;

  function __construct(){
    parent::__construct(array(), new StdClass());
  }
  
  function setCallback(&$callback){
    $this->callback = $callback;
  }

  function addT($t){
    if($this->t_count > 2000){
      call_user_func($this->callback, $this->getSimpleIndex(0));
      $this->triples = array();
      $this->t_count = 0;
    }
    parent::addT($t);
  }

}

class StreamingRDFXMLParser extends ARC2_RDFXMLParser {

  var $callback;

    function __construct(){
    parent::__construct(array(), new StdClass());
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
