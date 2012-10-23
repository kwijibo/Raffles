<?php
require_once 'vendor/autoload.php';
require_once 'lib/rafflesstore.php';

require_once 'specs/spechelpers.php';

$store = getRafflesStore(true);

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

  describe("Loading descriptions of things already described in the store", function(){
    it("should augment the existing descriptions", function(){
    $store = getRafflesStore();
    $input1 =       
      array(
        ex.'id/1' => array(
          ex.'name' => array(
            array('value' => "Jim")
          ),
        ),
      );

     $input2 =       
      array(
        ex.'id/1' => array(
          ex.'nickname' => array(
            array('value' => "Jimmy")
          ),
        ),
      );

     $input3 =       
      array(
        ex.'id/1' => array(
          ex.'age' => array(
            array('value' => "36")
          ),
        ),
      );



    $response = $store->load($input1);
    $response = $store->load($input2);
    $response = $store->load($input3);
    $description = $store->get(ex.'id/1');
    $merge = array_merge($input3[ex.'id/1'], $input2[ex.'id/1'], $input1[ex.'id/1']);
    expect($description[ex.'id/1'])->to_equal($merge);

    });
  });

  it("should let you get facets for a property", function(){
    $store = getRafflesStore();
    $data = file_get_contents('specs/test.ttl');
    $loadResponse = $store->loadData($data);
    $types = $store->getFacets('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
        $expected = array(
      'http://xmlns.com/foaf/0.1/Person' => 155, 
      'http://xmlns.com/foaf/0.1/Organization' => 8, 
      'http://purl.org/ontology/bibo/Book' => 7, 
      'http://xmlns.com/foaf/0.1/Agent' => 4, 
      'http://www.w3.org/2003/01/geo/wgs84_pos#SpatialThing' => 3
    );

    expect($types)->to_equal($expected);
    
  });

  it("should let you do free text search", function(){
    $store = getRafflesStore();
    $store->indexPredicates = false;
    $data = file_get_contents('specs/test.ttl');
    $loadResponse = $store->loadData($data);
    $results = $store->search("London");
    expect(empty($results))->to_equal(false);
  });

  it("should return a list of indexed rdf:type values", function(){
    // $store = getRafflesStore();
    // $store->indexPredicates = false;    
    // $data = file_get_contents('specs/test.ttl');
    // $loadResponse = $store->loadData($data);
    global $store;
    $results = $store->getTypes();
        $expected = array(
      'http://xmlns.com/foaf/0.1/Person' => 155, 
      'http://xmlns.com/foaf/0.1/Organization' => 8, 
      'http://purl.org/ontology/bibo/Book' => 7, 
      'http://xmlns.com/foaf/0.1/Agent' => 4, 
      'http://www.w3.org/2003/01/geo/wgs84_pos#SpatialThing' => 3
    );

    expect($results)->to_equal($expected);
  });

  it("should let you get matching descriptions for a (* p o)  triple pattern", function(){
    global $store;
    $results = $store->get(null, rdftype, biboBook);
    $expected = array();
    expect(count(array_keys($results)))->to_equal(7);
  });

  it("should let you query with an LDPath", function(){
    global $store;
    $results = $store->query("foaf:made/dct:date=1773");
    $uris = array_keys($results);
    expect($uris[0])->to_equal('http://example.org/ecco-tcp/person/Mrs_(anna_Letitia)_Barbauld_1743-1825');
  });
  it("should let you query with simple LDPath rdf:type=foaf:Person", function(){
    global $store;
    $results = $store->query("rdf:type=foaf:Person");
    $uris = array_keys($results);
    expect($uris[0])->to_equal('http://example.org/ecco-tcp/person/A_Alison');
});

  it("should tell you what namespaces are used in the data", function(){
    $data = array(
      ex.'people/tom' => array(
        ex.'vocab/name' =>  array(
          array('value' => 'Tom', 'type' => 'literal'),
        ),
      ),
    );
    $store = new \Raffles\RafflesStore('testdata');
    $store->reset();
    $store->load($data);
    expect($store->Index->getSubjectNamespaces())->to_equal(array(ex.'people/'));
    expect($store->getNamespaces())->to_equal(array(
      ex.'people/' => 'people',
      ex.'vocab/' => 'vocab',
    ));
 
  });

});


//\pecs\run();
?>
