<?php 
require 'indexfilter.php';
define('Geo_NS', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
class Index {

  var $subjects = '';
  var $_subjects = array();
  var $po = array();
  var $geo = array();
  var $object_id_counter = 'A';

  function __construct(){
  }


 function subjectIsIndexed($s){
    return $this->getSubject($s)? true : false;
  }

  /* 
   * gets term ID or creates one if none exists
   */
  function getTermID($s){
    $id = $this->getSubject($s);
    if($id===null) {
      $id = $this->object_id_counter++;
      if($this->addSubject($s, $id)){
        return $id;
      } else {
        throw new Exception("{$s} doesn't exist but couldn't be created in the Index ?!!!");
      }

    } else {
      return $id;
    }
  }

  function reloadIndex($p=false){
    if($p){
      if(isset($this->po[$p]) AND is_string($this->po[$p])){
        $filename = $this->po[$p];
        if($contents = file_get_contents($filename)){
          $this->po[$p] =unserialize($contents);
          if(!is_array($this->po[$p])){
            throw new Exception("Couldn't load the $p index");
          }
        }
      }
      return;
    } else {
      foreach($this->po as $p => $os){
        $this->reloadIndex($p);
      }
    }
  }

  function __get($name){

    switch($name){
      case 'subjects':
        if(empty($this->_subjects)){
          $this->_subjects = unserialize(file_get_contents($this->subjects));
        }
        return $this->_subjects;
      break;
      default:
      return $this->$name;
    }
  }

  function __set($name, $value){
    switch($name){
      case 'subjects':
        if(is_string($this->_subjects)){
          $this->_subjects = unserialize(file_get_contents($this->_subjects));
        }
        return $this->_subjects = $value;
      break;
      default:
      return $this->$name;
    }

  }

  /* 
   *  splits a URI by the last / in order to index 
   *  it as an associative array of firstpart => lastparts
   *   eg http://example.com/people/ => array(John => 1, Paul => 2, Ringo => 3, George=> 4)
   */
  private function splitUri($s){
    if(preg_match('@^(.+)(/.*)$@', $s, $m)){
      return array($m[1], $m[2]);
    }
    return array($s,'');
  }

  function getUriLatLong($s_uri){
    return isset($this->geo[$s_uri])? $this->geo[$s_uri] : null;
  }

  function setUriLatLong($s, $lat_long){
    $this->geo[$s]=$lat_long;
  }
  function getIDsByDistance($lat_long, $km){
    $return_ids = array();
    list($lat1,$long1) = explode(',',$lat_long);
    $this->reloadIndex(Geo_NS.'lat_long');
    if(isset($this->po[Geo_NS.'lat_long']) AND is_array($this->po[Geo_NS.'lat_long'])){
      foreach($this->po[Geo_NS.'lat_long'] as $lat_long2 => $ids ){
        list($lat2,$long2) = explode(',',$lat_long2);
        $actual_distance  = $this->distance($lat1, $long1, $lat2,$long2);
        if($actual_distance <= $km){
          $return_ids[$actual_distance] = $ids;
        }
      }
    }
    return $return_ids;
  }

 private function distance($lat1, $lng1, $lat2, $lng2){
    $pi80 = M_PI / 180;
    $lat1 *= $pi80;
    $lng1 *= $pi80;
    $lat2 *= $pi80;
    $lng2 *= $pi80;

    $r = 6372.797; // mean radius of Earth in km
    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;
    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $km = $r * $c;
    return $km;
  }
 
  function addSubject($s, $index_id,$overwrite=false){
    list($host,$local) = $this->splitUri($s);
    if(!$overwrite AND isset($this->subjects[$host][$local])){
        return false;
      }
    $this->subjects[$host][$local] = $index_id;
    return $index_id;
  }
  

  function replaceSubject($s, $new_id){
    $old_id = $this->getSubject($s);
    $this->addSubject($s, $new_id, true);
    $this->reloadIndex();
    foreach($this->po as $p => $os){
      foreach($os as $val => $ids){
        foreach($ids as $n => $id){
          if($id==$old_id){
            $this->po[$p][$val][$n]=$new_id;
          }
        }
      }
    }
  }
  
  function addPredicateObject($p,$o, $linenumbers){
    $this->reloadIndex($p);
    if(!is_array($linenumbers)) $linenumbers = array($linenumbers);
    if(!isset($this->po[$p])) $this->po[$p] = array();
    if(!isset($this->po[$p][$o])) $this->po[$p][$o] = array();
    $existingLineNumbers = $this->po[$p][$o];
      foreach($linenumbers as $linenumber){
        if(!in_array($linenumber, $existingLineNumbers)){ 
          array_push( $this->po[$p][$o], $linenumber );
          sort($this->po[$p][$o]);
        }
    }
    return $this->po[$p][$o];

  }

  function getPredicateObject($p,$o){
    $this->reloadIndex($p);
    if(!empty($this->po[$p]) 
      AND !empty($this->po[$p][$o])
    ) { return $this->po[$p][$o]; }
      else { return array();   }
  }

  function getSubject($s){ 
    list($host,$local) = $this->splitUri($s);
    if(!isset($this->subjects[$host]) || !isset($this->subjects[$host][$local])){
      return null;
    }
    return $this->subjects[$host][$local];  
  }

  function getSubjectByID($id){
    $subjects = $this->subjects;
    foreach($subjects as $host => $local_to_id){
      foreach($local_to_id as $local => $id_no){ 
        if($id_no===$id) return $host.$local;
      }
    }
    return null;
  }
    
  function getObject($o){ 
    $this->reloadIndex();
    $all_line_numbers=array();
    foreach($this->po as $p => $os){
      if(isset($os[$o])){
        $all_line_numbers = array_merge($os[$o]);
      }
    }
    return $all_line_numbers;
  }
  
  function getPredicate($p){
      $this->reloadIndex($p);
      $all_line_numbers = array();
      foreach($this->po[$p] as $o => $line_numbers){
        $all_line_numbers = array_merge($line_numbers, $all_line_numbers);
      }
      return $all_line_numbers;
  }

  function getPredicateValues($p){
    $this->reloadIndex($p);
    if(!isset($this->po[$p])) return array();
    return $this->po[$p];
  }

  function filterPredicateObjectIndex($ids){
    $filteredPo = array();
    $idHash = array_flip($ids);
    $this->reloadIndex();
    foreach($this->po as $p => $objs){
      foreach($objs as $o => $sIDs){
        if(count($sIDs) > 1){
          $intersect = array();
          foreach($sIDs as $sID){
            if(isset($idHash[$sID])) $intersect[]=$sID;
          }
          if(!empty($intersect)){
            $o = strval($o);
            $filteredPo[$p][$o] = $intersect;
          }
        }
      }
    }
    return $filteredPo;
  }

  function searchObject( $o, $p=false){
    $all_ids = array();
    if(empty($p)){
      $this->reloadIndex();
      $p = array_keys($this->po);
    }
    $p = (array)$p;
    foreach($p as $no => $property){
      $this->reloadIndex($property);
      foreach($this->po[$property] as $object => $ids){
        if(stripos($object,$o)!==false){
          $all_ids = array_merge($all_ids, $ids);
        }
      }
    }
    return $all_ids;
  }


  function getAll(){
    $ids = array();
    foreach($this->subjects as $host => $locals){
      $ids = array_merge($ids, array_values($locals));
    }
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
