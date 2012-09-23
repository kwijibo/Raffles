<?php

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
  
  function setPrefixes($prefixes){
    $this->prefixes = $prefixes;
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
      throw new Exception("No namespace for prefix {$prefix} is defined");
    }
    return $this->prefixes[$prefix].$localname;
  }

  function parse($ldpath){
    $triple_patterns = array();
    list($path,$value) = explode('=', $ldpath);
    $value_type = ($this->is_curie($value) OR $this->is_uri($value))? 'uri' : 'literal';
    $value = (!$this->is_curie($value))? $value : $this->curie_to_uri($value);
    $curies = explode('/', $path);
//    array_walk($curies, array(&$this, 'curie_to_uri'));
    $uris = array_reverse($curies);
    $var_name = 'a';
    foreach($uris as $no => $uri){
      $p = $this->curie_to_uri($uri);
      $o = ($no===0)? array( 'type' => $value_type, 'value'=> $value) : array( 'type' => 'variable', 'value' => $var_name);
      $s = ($no===0)? $var_name : ++$var_name;
      $triple_patterns[] = array(
        's' => array('type'=> 'variable', 'value'=> $s),
        'p' => array('type'=> 'uri', 'value'=> $p),
        'o' => $o,
      ); 
    }
    return $triple_patterns;
  }
}

?>
