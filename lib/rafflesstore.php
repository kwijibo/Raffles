<?php
require_once 'ldpath.php';
require_once 'index.php';
require_once 'hierarchicalindex.php';
require_once 'descriptionstore.php';
require_once 'streamingparsers.php';

class RafflesStore {
  var $Index;
  var $HierarchicalIndex;
  var $DescriptionStore;
  var $dirname;
  var $LDPath;
  var $_lastSet=array();
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
    "open" => "http://open.vocab.org/terms/",
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
    $this->HierarchicalIndex = new HierarchicalIndex($this->Index);
  }

  function addPrefix($prefix, $ns){
    $this->prefixes[$prefix] = $ns;
    $this->LDPath->setPrefixes($this->prefixes);
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
    $alreadyStoredDescriptions=array();
    $newDescriptions = array();
    foreach($descriptions as $s => $props){
      if($id = $this->Index->getSubject($s)){
        $alreadyStoredDescriptions[$s]=$id;
      } else {
        $newDescriptions[$s] = $props;
      }
    }
    
    $existingIDs = array_values($alreadyStoredDescriptions);
    $existingDescriptions = $this->describeIDs($existingIDs);

    $mergedDescriptions = merge($descriptions,$existingDescriptions);
    $replacements = array();
    foreach($existingDescriptions as $e_uri => $e_props){
      $id = $alreadyStoredDescriptions[$e_uri];
      $replacements[$id] = array($e_uri => $mergedDescriptions[$e_uri]);
    }
    $lineNumbers = $this->DescriptionStore->insertDescriptions($newDescriptions);
    $lineNumbers = array_merge($lineNumbers, $alreadyStoredDescriptions);
    $this->DescriptionStore->replaceDescriptions($replacements);
    gc_enable();
    gc_collect_cycles();
    gc_disable();
    foreach($descriptions as $s => $props){
      if(isset($lineNumbers[$s])){
        $lineNumber = $lineNumbers[$s];
      } else if(isset($alreadyStoredDescriptions[$s])) {
        $lineNumber = $alreadyStoredDescriptions[$s]; 
      } else {
        throw new Exception($s.' somehow not indexed');
      }
      $lineNumber = $lineNumbers[$s];
      $this->Index->addSubject($s, $lineNumber);
      $count['s']++;

      //geo
      if(isset($props[Geo_NS.'lat_long'])){
         $lat_long = $props[Geo_NS.'lat_long'][0]['value'];
      } else if(isset($props[Geo_NS.'lat']) && isset($props[Geo_NS.'long'])){
        $lat_long = $props[Geo_NS.'lat'][0]['value'].','.$props[Geo_NS.'long'][0]['value'];
      }

      if(isset($lat_long)){ 
        $lat_long = trim($lat_long);
        $this->Index->setUriLatLong($s, $lat_long); 
        $this->Index->addPredicateObject(Geo_NS.'lat_long', $lat_long, $lineNumber);
      }

      foreach($props as $p => $objs){
        $count['p']++;
        foreach($objs as $obj){
          if(!$this->indexPredicates OR in_array($p, $this->indexPredicates)){
            $this->Index->addPredicateObject($p, $obj['value'], $lineNumber);
          }
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
    require_once 'arc/ARC2.php';
    $parser = ARC2::getRDFParser();
    $parser->parse('',$data);
    return $this->load($parser->getSimpleIndex(0));
    $graph = new EasyRdf_Graph();
    $graph->parse($data);
    return $this->load($graph->toArray());
  }

  function loadDataFile($filename){
    require_once 'streamingparsers.php';
    $extension = array_pop(explode('.',$filename));
    switch($extension){
      case 'ttl':
      case 'nt':
      case 'n3':
        $parser = new StreamingTurtleParser();
        break;
      case 'rdf':
      case 'xml':
        $parser = new StreamingRDFXMLParser();
        break;
      default:
        throw Exception("No Handler for Data File extension: $extension");
        break;
    }
    $callback = array(&$this, 'load');
    $parser->setCallback($callback);
    $parser->parse($filename);
    $leftovers = $parser->getSimpleIndex(0);
    $this->load($leftovers);
  }

  function createHierarchicalIndex(){
    $ids = $this->Index->getAll();
    $batches = array_chunk($ids, 50);
    foreach($batches as $batch){
      $descriptions = $this->describeIDs($batch);
      foreach($descriptions as $uri => $props){
        foreach($props as $p => $objs){
          foreach($objs as $o){
            if(isset($o['type']) AND ($o['type']=='uri' OR $o['type']=='bnode')){
              $this->HierarchicalIndex->addTriple($uri,$p,$o['value']);
            }
          }
        }
      }
    }
  }

  function relatedUris($uri){
    return $this->HierarchicalIndex->getRelatedByURI($uri);
  }
  
  function getFacets($p){
    $propertyIndex = $this->Index->getPredicateValues($p);
    foreach($propertyIndex as $o => $lns){
      $propertyIndex[$o] = count($lns);
    }
    arsort($propertyIndex);
    return $propertyIndex;
  }

  function getTypes(){
    return $this->getFacets('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
  }

  function search($o_text, $property=false){
    $ids = $this->Index->searchObject($o_text,$property);
    $this->_lastSet = $ids;
    return $this->DescriptionStore->getDescriptionsByIDs($ids);
  }

  function query($path, $limit=50,$offset=0){
    $triples = $this->LDPath->parse($path);
    $ids = $this->Index->query($triples);
    $this->_lastSet = $ids;
    return $this->DescriptionStore->getDescriptionsByIDs(array_slice((array)$ids, $offset, $limit));
  }

  function getFacetsForLastQuery(){
    $po =  $this->Index->filterPredicateObjectIndex($this->_lastSet);
    $p_index = array();
    foreach($po as $p => $os){
      $p_index[$p] = count(array_keys($os));
      foreach($os as $o => $ids){
        $o = strval($o);
        $po[$p][$o] = count($ids);
      }
      arsort($po[$p]);
      $po[$p] = array_slice($po[$p],0,15,true);
    }
    asort($p_index);
    foreach($p_index as $p => $count){
      $p_index[$p] = $po[$p];
    }
    return $p_index;
  }

  function distance($uri, $km=30){
    if($lat_long = $this->Index->getUriLatLong($uri)){
      $ids_by_distance = $this->Index->getIDsByDistance($lat_long, $km);
      $ids = array();
      foreach($ids_by_distance as $distance => $d_ids){
        array_splice($ids,0,0,$d_ids);
      }
      $this->_lastSet = $ids;
      return $this->describeIDs($ids);
    } else {
      return array();
    }
  }

  function __destruct() {
    $index_file_name = $this->dirname . DIRECTORY_SEPARATOR .'index';
    $descriptions_file_name = $this->dirname . DIRECTORY_SEPARATOR .'descriptions';
    if(!is_file($index_file_name) OR is_file($descriptions_file_name) AND filemtime($descriptions_file_name) > filemtime($index_file_name) ){
      foreach($this->Index->po as $p => $o_ids){
        if(is_array($o_ids)){
          $filename = $this->dirname . DIRECTORY_SEPARATOR . 'index_po_' .urlencode($p);
          file_put_contents($filename, serialize($o_ids), LOCK_EX);
          $this->Index->po[$p] = $filename;
        }
      }
      file_put_contents($index_file_name, serialize($this->Index), LOCK_EX);
    }
  }

  function reset(){
    foreach(glob($this->dirname . DIRECTORY_SEPARATOR .'*') as $full_filepath){
      if(is_file($full_filepath)){
        unlink($full_filepath);
      }
    }
    $this->Index = new Index();
    $this->DescriptionStore = new DescriptionStore($this->dirname . DIRECTORY_SEPARATOR . 'descriptions');
  }
}
