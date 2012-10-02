<?php
class HierarchicalIndex {

  var $index=array();
  var $links_to = array();
  var $Index;

  function __construct(&$Index){
    $this->Index = $Index;
  }

  function addTriple($s_uri, $p_uri, $o_uri){
    list($s, $p, $o) = array(
      $this->Index->getTermID($s_uri),
      $this->Index->getTermID($p_uri),
      $this->Index->getTermID($o_uri),
    );
    $this->indexTriple($s,$p,$o);
  }

  //return false if
  //  shorter path to object exists
  //  shorter path fails
  //fail path if
  //  s == o
  //  linker_to_s == o
  //
  //add s[p][o]
  //linker_to_s[path_to_s+p][o]

    function indexTriple($s, $path, $o){

      return false;
      if(!isset($this->_related[$s_uri])){
        $this->_related[$s_uri] = array();
      }
      if(!isset($this->_related_from[$s_uri])){
        $this->_related_from[$s_uri] = array();
      }
      if(!isset($this->_related_from[$r_uri])){
        $this->_related_from[$r_uri] = array();
      }
      if(!isset($this->_related[$s_uri][$p_path])){
        $this->_related[$s_uri][$p_path] = array();
      }

    foreach($this->_related[$s_uri] as $shorter_path => $objs){
      if($objs=='END' AND strpos($p_path, $shorter_path)===0){
        echo "\n\n failed: ";
        print_r(array($s_uri, $p_path, $r_uri));
        echo "\n because of $shorter_path";
        return false;
      } else if(strpos($p_path, $shorter_path)!==false AND (is_array($objs) AND in_array($r_uri, $objs))){
        echo "\nfailed $p_path because shorter path $shorter_path exists\n";
        return false; 
      }
    }

    if($this->_related[$s_uri][$p_path]=='END'){
      
      return false;

    } else if($s_uri==$r_uri){
      foreach($this->_related[$s_uri][$p_path] as $prev_r_uri){
        $n = array_search($s_uri, $this->_related_from[$prev_r_uri]);
        unset($this->_related_from[$prev_r_uri][$n]); 
      }
      print_r(array("already related", $s_uri, $r_uri));
      $this->_related[$s_uri][$p_path] = 'END';
      return false;
    } else {
      $this->_related[$s_uri][$p_path][]=$r_uri;
      if(!in_array($s_uri, $this->_related_from[$r_uri])){
        $this->_related_from[$r_uri][]=$s_uri;
      }
     $links_to_s = $this->_related_from[$s_uri];
     foreach($links_to_s as $s_rf_uri){
       $linker_paths = $this->_related[$s_rf_uri];
       if(!in_array($s_rf_uri, $this->_related_from[$r_uri])){
         $this->_related_from[$r_uri][]=$s_rf_uri;
       }
        foreach($linker_paths as $rf_path => $rf_obs){
          if($rf_obs!='END' AND in_array($s_uri, $rf_obs)){
            $deeper_path = $rf_path.'|'.$p_path;
            if( 
              !isset($this->_related[$s_rf_uri][$deeper_path]) 
              OR ($this->_related[$s_rf_uri][$deeper_path]!='END'
              AND !in_array($s_uri, $this->_related[$s_rf_uri][$deeper_path])
              )
            ){
              $this->addSubjectUriRelation($s_rf_uri, $deeper_path, $r_uri);
            }
          }
        }
      }
    }
  }

  function getRelatedByURI($uri){
    return array();
    if(!empty($this->_related) && isset($this->_related[$uri])){
      $rel = array();
      foreach(array_values($this->_related[$uri]) as $set){ 
        if(is_array($set)) array_splice($rel,0,0,$set);
      }
      return $rel; 
    } else {
      $id = $this->getSubject($uri);
      if($id){
        return $this->getRelated($id);
      } else {
        return array();
      }
    }
  }

}

?>
