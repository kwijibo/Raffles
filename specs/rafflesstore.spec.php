<?php
require_once 'vendor/autoload.php';
require_once 'lib/rafflesstore.php';

define('ex', "http://example.com/ns/");

function getRafflesStore(){
  $dirname = 'testdata';
  $store = new RafflesStore($dirname);
  $store->reset();
  return $store;
}

describe("Raffles Store", function(){
  it("should let you load some RDF", function(){
    $store = getRafflesStore();
    $response = $store->load(
      array(
        ex.'id/1' => array(
          ex.'name' => array(
            array('value' => "Jim"),
            array('value' => 'James')
          ),
          ex.'ages' => array(
            array('value' => "65"),
          ),
 
        ),
      )
    );
    expect($response)->to_equal(
      array(
        's' => 1,
        'p' => 2,
        'o' => 3
      )
    );
  });


  it("should let you get some rdf out again", function(){
    $store = getRafflesStore();
    $input =       
      array(
        ex.'id/1' => array(
          ex.'name' => array(
            array('value' => "Jim")
          ),
        ),
      );

    $response = $store->load($input);
    $description = $store->get(ex.'id/1');
    expect($description)->to_equal($input);
  });

  it("should let you load RDF from a turtle file", function(){
    $store = getRafflesStore();
    $data = file_get_contents('specs/test.ttl');
    $loadResponse = $store->loadData($data);
    $description = $store->get('http://example.org/ecco-tcp/text/CW3305901409');
    expect(array_keys($description))->to_equal(array('http://example.org/ecco-tcp/text/CW3305901409'));
  });

  it("should let you get facets for a property", function(){
    $store = getRafflesStore();
    $data = file_get_contents('specs/test.ttl');
    $loadResponse = $store->loadData($data);
    $types = $store->getFacets('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
    expect(empty($types))->to_equal(false);
    
  });

  it("should let you do free text search", function(){
    $store = getRafflesStore();
    $store->indexPredicates = false;
    $data = file_get_contents('specs/test.ttl');
    $loadResponse = $store->loadData($data);
    $results = $store->search("London");
    expect(empty($results))->to_equal(false);
  });

});


\pecs\run();
?>
