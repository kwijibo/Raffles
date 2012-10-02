<?php
class AcceptHeader {

    function getAcceptHeader(){
        if(isset($_SERVER['HTTP_ACCEPT'])) return trim($_SERVER['HTTP_ACCEPT']);
        else return null;
    }
    
    function getAcceptTypes($defaultTypes = array()){
        $header = self::getAcceptHeader();
        $mimes = explode(',',$header);
    	$accept_mimetypes = array();

        foreach($mimes as $mime){
        $mime = trim($mime);
    		$parts = explode(';q=', $mime);
    		if(count($parts)>1){
    			$accept_mimetypes[$parts[0]]=strval($parts[1]);
    		}
    		else {
    			$accept_mimetypes[$mime]=1;
    		}
    	}
  /* prefer html, then xhtml, then anything in the default array, to mimetypes with the same value. this is because WebKit browsers (Chrome, Safari, Android) currently prefer xml and even image/png to html */
  $defaultTypes = array_merge(array('text/html', 'application/xhtml+xml'), $defaultTypes);
	foreach($defaultTypes as $defaultType){
		if(isset($accept_mimetypes[$defaultType])){	
			$count_values = array_count_values($accept_mimetypes);
			$defaultVal = $accept_mimetypes[$defaultType];
			if($count_values[$defaultVal] > 1){
				$accept_mimetypes[$defaultType]=strval(0.001+$accept_mimetypes[$defaultType]);
			}
		}
  }
    	arsort($accept_mimetypes);
    	return array_keys($accept_mimetypes);
    }
    
    function hasAcceptTypes(){
        $acceptheader = $this->getAcceptHeader();
        if(empty($acceptheader)){
           return false; 
        } else {
            return true;
        }
    }

}

  set_time_limit(0);
  require 'lib/rafflesstore.php';
  require_once 'vendor/autoload.php';

  define('NS', 'http://keithalexander.co.uk');

  function plural($word){
    if($word=='Person'){
      return 'People';
    } else {
      return $word.'s';
    }
  }
  function curie($uri){
    global $namespaces;
    if(preg_match('/^(.+[\/#])([^\/#]+)$/', $uri, $m)){
    $ns = $m[1];
    $local = $m[2];
    return isset($namespaces[$ns])? $namespaces[$ns].':'.$local : $uri;
    } else {
      return $uri;
    }
  }

  function curie_to_uri($curie){
    global $prefixes;
    list($prefix, $local) = explode(":", $curie);
    return $prefixes[$prefix].$local;
  }

  function local($uri){
    global $namespaces;
    if(preg_match('/([^:\/#]+)$/', $uri, $m)){
      $local = $m[1];
      $local = str_replace('_', ' ', urldecode($local));
      return ucwords(preg_replace('/([a-z])([A-Z])/','$1 $2', $local));
    }  else {
      return $uri;
    }
  }

  function pathescape($o){
    if($o['type']=='uri'){
      $v = curie($o['value']);
    } else {
      $v = $o['value'];
    }
    return urlencode($v);
  }


function label($props, $uri='Something'){
  global $prefixes;
  extract($prefixes);
  $labelPs = array($dct.'title', $foaf.'name', $rdfs.'label');
  foreach($labelPs as $p){
    if(isset($props[$p])) return $props[$p][0]['value'];
  } 
  $type = 'Thing';
  if(isset($props[$rdf.'type'])){
    $type = $props[$rdf.'type'][0]['value'];
  }
  return "A ".curie($type);
}

function getQuery(){
  foreach($_GET as $k => $v){
    if(strpos($k, '_')!==0) return "{$k}=".urldecode($v);
  }
  return '';
}
  $Config = json_decode(file_get_contents('config.json'));

session_start();
  if(isset($_GET['_dataset'])){
    $dataset =  $_GET['_dataset'];
    $_SESSION['_dataset'] = $dataset;
  } else if(isset($_SESSION['_dataset'])){
    $dataset = $_SESSION['_dataset'];
  } else {
    $dataset = 'climb';
  }
  

  $store = new RafflesStore(__DIR__.'/'.$dataset);
  $prefixes = $Config->$dataset->prefixes;
  foreach($prefixes as $prefix => $ns){
    $store->addPrefix($prefix, $ns);
  }
  
  $prefixes = $store->prefixes;
  $namespaces = array_flip($prefixes);


  
  foreach($Config->$dataset->index_predicates as $indexCurie){
      $store->indexPredicates[]=curie_to_uri($indexCurie);
  }
  if(empty($Config->$dataset->index_predicates)){
    $store->indexPredicates=false;
  }

  $title = ucwords($dataset);
 
  if(isset($_GET['_reload'])){
    set_time_limit(0);
    $store->reset();
    $data_file = $Config->$dataset->data;
    if(!is_file($data_file)){
      throw new Exception("$data_file could not be found");
    }
    $store->loadDataFile($data_file);
  }

  $types = $store->getTypes();
  $query = getQuery();
  $page = 1;
  $offset = (isset($_GET['_page']) && $page = $_GET['_page'])? ($_GET['_page']-1)*10 : 0;


  if(!empty($query)){
      //query based title
    list($path, $value) = explode('=',$query);
    $value = curie($value);
    $title = local($value);
    if($path=='rdf:type'){ $title = plural($title); }
    else { $title = local($path).': '.$title; }
    $data = $store->query($query, 10, $offset);

  } else if(isset($_GET['_search']) && $search = $_GET['_search']){
    $data = $store->search($search);

  } else if(isset($_GET['_near'])) {
    $distance = (isset($_GET['_near_distance']))? (float) $_GET['_near_distance'] : 20;
    $data = $store->distance($_GET['_near'], $distance);
    $title = "Near ". local($_GET['_near']);
  } else if(isset($_GET['_related'])) {
      $title = 'Related: '.local($_GET['_related']);
      $data = $store->get(null, null, $_GET['_related']);
  } else {
    if(isset($_GET['_uri'])){
      $requestUri = $_GET['_uri'];
    } else {
      $requestUri = NS.$_SERVER['REQUEST_URI'];
    }
    $data = $store->get($requestUri);
    if(empty($data)){
      header("HTTP/1.0 404 Not Found");
    }
    $page =1;
  }
$facets = $store->getFacetsForLastQuery();

$acceptTypes = AcceptHeader::getAcceptTypes();
foreach($acceptTypes as $mimetype){
  switch($mimetype){
  
    case 'text/html':
    case '*/*':
    require 'html/template.html';
    exit;

    case 'application/json';
     echo json_encode($data);
    exit;
  }
}

?>
