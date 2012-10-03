<?php
set_time_limit(0);
require '../lib/rafflesstore.php';
require '../vendor/autoload.php';
require 'helpers.php';


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
  
$store = new RafflesStore('.'.DIRECTORY_SEPARATOR.$dataset);
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
//       $store->loadData(file_get_contents($data_file));
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
      //     $data = $store->get(null, null, $_GET['_related']);
      $data = $store->related($_GET['_related']);
  } else {
    if(isset($_GET['_uri'])){
      $requestUri = $_GET['_uri'];
    } else {
      $requestUri = (isset($Config->$dataset->urispace)? $Config->$dataset->urispace : 'http://'.$_SERVER['SERVER_NAME'] ).$_SERVER['REQUEST_URI'];
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
    require 'template.html';
    exit;

    case 'application/json';
     echo json_encode($data);
    exit;
  }
}

?>
