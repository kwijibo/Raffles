<?php
require_once 'vendor/autoload.php';
require_once 'lib/ldpath.php';

require_once 'specs/spechelpers.php';

describe("LDPath", function(){
  it("should turn an LDPath string into an associative array that can be processed into index queries", function(){
    $path = new LDPath();
    $actual = $path->parse("foaf:made/dct:date=1728"); 
    $expected = getTriplePatternArray();
    expect($actual)->to_equal($expected);
  });

  it("should leave http uris as they are", function(){
    $path = new LDPath();
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

  it("should parse CURIEs in object position into URIs", function(){
    $path = new LDPath();
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
  
  });
  it("should treat non-CURIE object-position as literals", function(){
  
  });
});
//\pecs\run();
?>
