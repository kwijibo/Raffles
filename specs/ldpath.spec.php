<?php
require_once 'vendor/autoload.php';
//require_once 'lib/ldpath.php';
require_once 'specs/spechelpers.php';

describe("LDPath", function(){
  it("should turn an LDPath string into an associative array that can be processed into index queries", function(){
    $path = new \Raffles\LDPath();
    $actual = $path->parse("foaf:made/dct:date=1728"); 
    $expected = getTriplePatternArray();
    expect($actual)->to_equal($expected);
  });

  it("should leave http uris as they are", function(){
    $path = new \Raffles\LDPath();
    $actual = $path->parse("rdf:type=http://example.org/Thing"); 
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'), 
        'p' => array('type' => 'uri', 'value' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'), 
        'o' => array('type' => 'uri', 'value' => 'http://example.org/Thing')
      )
    );
    expect($actual)->to_equal($expected);

  
  });

  it("should recognise literals with escaped colons as literals, not curies", function(){
    $path = new \Raffles\LDPath();
    $actual = $path->parse("rdfs:label=Wanted\:bugs"); 
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'), 
        'p' => array('type' => 'uri', 'value' => 'http://www.w3.org/2000/01/rdf-schema#label'), 
        'o' => array('type' => 'literal', 'value' => 'Wanted:bugs')
      )
    );
    expect($actual)->to_equal($expected);

  });

  it("should parse CURIEs in object position into URIs", function(){
    $path = new \Raffles\LDPath();
    $actual = $path->parse("rdf:type=foaf:Person"); 
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'), 
        'p' => array('type' => 'uri', 'value' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'), 
        'o' => array('type' => 'uri', 'value' => 'http://xmlns.com/foaf/0.1/Person')
      )
    );
    expect($actual)->to_equal($expected);

  });

  it("should parse CURIEs into URIs",function(){
      $ldpath = new \Raffles\LDPath(array('foo'=>ex));
      $actual = $ldpath->curie_to_uri('foo:bar');
      expect($actual)->to_equal(ex.'bar');
  });
  it("should treat non-CURIE object-position as literals", function(){
    $path = new \Raffles\LDPath();
    $actual = $path->parse("foaf:name=Arthur"); 
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'), 
        'p' => array('type' => 'uri', 'value' => 'http://xmlns.com/foaf/0.1/name'), 
        'o' => array('type' => 'literal', 'value' => 'Arthur')
      )
    );
    expect($actual)->to_equal($expected);

  });
  describe("foaf:name;_search=foo ", function(){
    it("should have an object of type 'filter' and filter_type 'search' ", function(){
    
    $path = new \Raffles\LDPath();
    $actual = $path->parse("foaf:name;_search=foo");
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'), 
        'p' => array('type' => 'uri', 'value' => 'http://xmlns.com/foaf/0.1/name'), 
        'o' => array('type' => 'filter', 'value' => 'foo', 'filter' => '_search')
      )
    );
    expect($actual)->to_equal($expected);

    });
  }); 
  
  describe("_search=foo ", function(){
    it("should have a predicate of type 'variable' and an object of type 'filter' and filter_type 'search' ", function(){
    
    $path = new \Raffles\LDPath();
    $actual = $path->parse("_search=foo");
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'b'), 
        'p' => array('type' => 'variable', 'value' => 'a'), 
        'o' => array('type' => 'filter', 'value' => 'foo', 'filter' => '_search')
      )
    );
    expect($actual)->to_equal($expected);

    });
  });

  describe("*/ex:bar=foo ", function(){
    it("should parse the * as a wildcard that traverse the graph outwards from the restriction to the right ", function(){
    
    $path = new \Raffles\LDPath();
    $path->prefixes['ex'] = 'http://example.com/';
    $actual = $path->parse("*/ex:bar=foo");
    $expected = array(
      array(
        's' => array('type' => 'variable', 'value' => 'a'), 
        'p' => array('type' => 'uri', 'value' => 'http://example.com/bar'), 
        'o' => array('type' => 'literal', 'value' => 'foo')
      ),
      array(
        's' => array('type' => 'variable', 'value' => 'c'), 
        'p' => array('type' => 'variable', 'value' => 'b'), 
        'o' => array('type' => 'variable', 'value' => 'a')
      )
    );
    expect($actual)->to_equal($expected);

    });
  });


});
\pecs\run();
?>
