<?php
require_once 'ldpath.php';
require_once 'index.php';
require_once 'descriptionstore.php';

class RafflesStore {
  var $Index;
  var $DescriptionStore;
  var $dirname;
  var $LDPath;
  var $prefixes = array(
    "foaf" =>	"http://xmlns.com/foaf/0.1/",
    "rdf" =>	"http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "rdfs" =>	"http://www.w3.org/2000/01/rdf-schema#",
    "owl" =>	"http://www.w3.org/2002/07/owl#",
    "dcterms" =>	"http://purl.org/dc/terms/",
    "dc" =>	"http://purl.org/dc/elements/1.1/",
    "dct" =>	"http://purl.org/dc/terms/",
    "schema" =>	"http://schema.org/",
    "bibo" =>	"http://purl.org/ontology/bibo/",
    "gr" => "http://purl.org/goodrelations/v1#",
    "geo" => "http://www.w3.org/2003/01/geo/wgs84_pos#",
  );
  var $indexPredicates = array('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');

  function __construct($dirname){
    $this->dirname = $dirname;
    if(!is_dir($this->dirname)){
      if(!mkdir($this->dirname)){
        throw new Exception("Couldn't create directory $this->dirname");
      }
    }
    $index_file = $this->dirname . DIRECTORY_SEPARATOR .'index';
    $this->Index = new Index();
    if(file_exists($index_file)){
      $index = unserialize(file_get_contents($index_file));
      if(is_object($index)){
        $this->Index = $index;
      }
    }
    $this->DescriptionStore = new DescriptionStore($dirname . DIRECTORY_SEPARATOR . 'descriptions');
    $this->LDPath = new LDPath($this->prefixes);
  }

  function addNamespacePrefix($prefix, $ns){
    $this->prefixes[$prefix] = $ns;
  }
  
  function setPrefixes($prefixes){
    $this->prefixes = $prefixes; 
    $this->LDPath->setPrefixes($prefixes);
  }

  function load($descriptions){
    $count = array(
      's' => 0,
      'p' => 0,
      'o' => 0
    );
    $lineNumbers = $this->DescriptionStore->insertDescriptions($descriptions);
    foreach($descriptions as $s => $props){
      $lineNumber = $lineNumbers[$s];     
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
    $IDs = $this->Index->filter($s,$p, $o)->ids();   
    return $this->describeIDs(array_slice((array)$IDs, $offset, $limit));
  }



  private function describeIDs($ids){
    return $this->DescriptionStore->getDescriptionsByIDs($ids);
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

  function getTypes(){
    return $this->getFacets('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
  }

  function search($o_text, $property=false){
    $ids = $this->Index->searchObject($o_text,$property);
    return $this->DescriptionStore->getDescriptionsByIDs($ids);
  }

  function query($path, $limit=50,$offset=0){
    $triples = $this->LDPath->parse($path);
    $ids = $this->Index->query($triples);
    return $this->DescriptionStore->getDescriptionsByIDs(array_slice((array)$ids, $offset, $limit));
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
