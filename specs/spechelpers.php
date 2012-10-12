<?php
define('testUri', "http://example.com/foo/123");

define('ex', "http://example.com/ns/");
define('biboBook', 'http://purl.org/ontology/bibo/Book' );
define('rdftype', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' );
define('dct', 'http://purl.org/dc/terms/');
define('foaf', 'http://xmlns.com/foaf/0.1/');

require_once 'lib/rafflesstore.php';

function getIndexWithBooks(){
  $data = array(
   #0 
      'a' => array( 
        'date' => array( array('value' => '1660') ) ,
        'imprint' => array( array('value' => 'London') ) 
      ),
     #1 
      'b' => array( 
            'date' => array( array('value' => '1660') ) ,
            'imprint' => array( array('value' => 'Paris') ),

            ),
 #2     
      'c' => array( 
          'date' => array( array('value' => '1780') ),
          'imprint' => array( array('value' => 'London') ),
        ),
  #3      
      'd' => array( 
          'printed' => array( array('value' => 'a'),  array('value' => 'b')  ),
        ),
    #4    
      'e' => array( 
          'printed' => array( array('value' => 'c'),  array('value' => 'b')  ),
        ),


    );
    $Index = new \Raffles\Index();
    $i = 0;
    foreach($data as $s => $ps){
      $Index->addSubject($s, $i);
      foreach($ps as $p => $os){
        foreach($os as $o){
          $Index->addPredicateObject($p, $o['value'], $i);
        }
      }
      $i++;
    } 
 return $Index;
}

function getFilter(){
  $Index = getIndexWithBooks();
  $Filter = new \Raffles\IndexFilter($Index); 
  return $Filter;
}

function getStore(){
  $store = new \Raffles\DescriptionStore(__DIR__.DIRECTORY_SEPARATOR.'testdata'.DIRECTORY_SEPARATOR.'descriptions');
  $store->reset();
  return $store;
}

function getIndex(){
  $index = new \Raffles\Index();
  return $index;
}

function getTriplePatternArray(){
  return array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'),
        'p' => array('type' => 'uri', 'value' => dct.'date'),
        'o' => array('type' => 'literal', 'value' => '1728'),
      ),
      array(
        's' => array('type' => 'variable', 'value' => 'b'),
        'p' => array('type' => 'uri', 'value' => foaf.'made'),
        'o' => array('type' => 'variable', 'value' => 'a'),
      )
   );

}

function getRafflesStore($load=false){
  $dirname = __DIR__.DIRECTORY_SEPARATOR.'testdata';
  $store = new \Raffles\RafflesStore($dirname);
  $store->indexPredicates = false;
  $store->reset();
  if($load){
    $data = file_get_contents('specs/test.ttl');
    $store->loadData($data);
  }
  return $store;
}

?>
