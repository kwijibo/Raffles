<?php
namespace Raffles;

class LDPath {

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

  var $filters = array(
    '_search','_min','_max','_near'
  );
  
  function __construct($prefixes=array()){
    $this->setPrefixes($prefixes);
  }
  function setPrefixes($prefixes){
    $this->prefixes = array_merge( $prefixes, $this->prefixes);
  }
  function is_curie($str){
    return (strpos( $str, ':') AND !strpos( $str, '/'))? true : false;
  }

  function is_uri($str){
    return (strpos($str, '://') OR strpos($str, 'urn:')===0)? true : false;
  }

  function curie_to_uri($curie){
    list($prefix,$localname) = explode(':', $curie);
    if(!isset($this->prefixes[$prefix])){
      throw new LDPathException("No namespace for prefix {$prefix} is defined");
    }
    return $this->prefixes[$prefix].$localname;
  }

  function term_to_uri($term){
    if($this->is_uri($term)) return term;
    else if ($this->is_curie($term)) return $this->curie_to_uri($term);
    else return $term;
  }

  function parse($ldpath){
    $triple_patterns = array();
    list($path,$value) = explode('=', $ldpath);
    $value_type = ($this->is_curie($value) OR $this->is_uri($value))? 'uri' : 'literal';
    $value = (!$this->is_curie($value))? $value : $this->curie_to_uri($value);
    $curies = explode('/', $path);
//    array_walk($curies, array(&$this, 'curie_to_uri'));
    $path_parts = array_reverse($curies);
    $var_name = 'a';
    foreach($path_parts as $no => $part){
      $filter = false;
      if($no===0){
        if(strpos($part, ';')){
          list($p,$filter) = explode(';',$part);
          $value_type = 'filter';
          $p = $this->term_to_uri($p);
          $p_type='uri';
        } else if(strpos($part,'_')===0) {
          $p_type = 'variable';            
          $value_type = 'filter';
          $filter = $part;
        } else {
          $p = $this->term_to_uri($part);
          $p_type='uri';
        }
      } else {
        $p = $this->term_to_uri($part);
        $p_type='uri';
      }
            
      if($filter AND !in_array($filter, $this->filters)){
        throw new LDPathException("$filter is not a recognised filter");
      }

      $o = ($no===0)? array( 'type' => $value_type, 'value'=> $value) : array( 'type' => 'variable', 'value' => $var_name);
      
      if($p_type=='variable'){
        $p = $var_name++;
      }
      
      $s = ($no===0)? $var_name : ++$var_name;



      if($value_type=='filter') $o['filter'] = $filter;
      $triple_patterns[] = array(
        's' => array('type'=> 'variable', 'value'=> $s),
        'p' => array('type'=> $p_type, 'value'=> $p),
        'o' => $o,
      ); 
    }
    return $triple_patterns;
  }
}

class LDPathException extends \Exception {}
?>
