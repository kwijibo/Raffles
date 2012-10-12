<?php
namespace Raffles;
class HierarchicalIndex {

  var $paths_from=array();
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
    $this->indexPath($s,$p,$o);
  }


  function setupIndexes($s, $path, $o){
      if(!isset($this->paths_from[$s])){
        $this->paths_from[$s] = array();
      }
      if(!isset($this->links_to[$s])){
        $this->links_to[$s] = array();
      }
      if(!isset($this->links_to[$o])){
        $this->links_to[$o] = array();
      }
      if(!isset($this->paths_from[$s][$path])){
        $this->paths_from[$s][$path] = array();
      }  
  }

  function undoPath($s,$path,$o){
    if(!is_array($this->paths_from[$s][$path])) return false;
    foreach($this->paths_from[$s][$path] as $i => $existing_value){
        $n = array_search($s, $this->links_to[$existing_value]);
        unset($this->links_to[$existing_value][$n]); 
    }
    $this->paths_from[$s][$path]='!';
  }

  function indexPath($s, $path, $o){
  
    $this->setupIndexes($s,$path,$o);

    if($this->paths_from[$s][$path]!='!' AND in_array($o, $this->paths_from[$s][$path])) return true;

    if($s==$o){
      $this->undoPath($s,$path,$o); //undo shorter paths
      foreach($this->paths_from[$s] as $existing_path => $vals){
        if(strpos($existing_path.'|',$path.'|')===0){
          $this->undoPath($s,$existing_path, null);
        }
      }
      return false;
    }
    foreach($this->paths_from[$s] as $existing_path => $values){
      if(strpos($path.'|',$existing_path.'|')===0 AND $values==='!'){
        return false;
      } else if(is_array($values) AND in_array($o, $values)) {
        $this->undoPath($s,$path,$o);
        return false;
      }
    }

    $this->paths_from[$s][$path][]=$o;
    $this->links_to[$o][]=$s;

    foreach($this->links_to[$s] as $linker_to_s){
      foreach($this->paths_from[$linker_to_s] as $path_to => $values){
        if($values!=='!' AND in_array($s,$values)){
          $path_to_o = $path_to.'|'.$path;
          $this->indexPath($linker_to_s, $path_to_o, $o);
        }
      }
    }

    if(!isset($this->paths_from[$o])) return;

    foreach($this->paths_from[$o] as $path_from_o => $o_os){
      if($o_os!=='!'){
        foreach($o_os as $os_o ){
          $path_to_os_o = $path.'|'.$path_from_o;
          $this->indexPath($s, $path_to_os_o, $os_o);
        }
      }
    }
  }

  function getRelatedIDs($id){
    $related = array();
    if(isset($this->paths_from[$id])){
      foreach($this->paths_from[$id] as $path => $vals){
        if(is_array($vals) AND !empty($vals)){
          $related = array_merge($related, $vals);
        }
      }
    }
   // $related = array_merge($related, $this->links_to[$id]);
    return $related;   

  }

  function getRelatedByURI($uri){
    $id = $this->Index->getSubject($uri);
    $related = array();
    if($id AND isset($this->paths_from[$id])){
      foreach($this->paths_from[$id] as $path => $vals){
        if(is_array($vals)){
          foreach($vals as $val_id){
            $related[]=$this->Index->getSubjectByID($val_id);
          }
        }
      }
    }
    return $related;   
  }
  

}

?>
