<?php

require_once 'lib/indexfilter.php';
require_once 'lib/index.php';
require_once 'vendor/autoload.php';
require_once 'specs/spechelpers.php';


describe("Index Filter", function(){
  it("should return a list of matching index IDs", function(){
    $Filter = getFilter();
    $results = $Filter->filter(null, 'date', '1660')->ids();
    expect($results)->to_equal(array(0,1));
  });
  it("should be able to filter down indefinitely", function(){
    $Filter = getFilter();
    $results = $Filter->filter(null, 'imprint', 'London')->filter(null,'date', '1780')->ids();
    expect($results)->to_equal(array(2)); 
  });
});
describe("an empty IndexFilter", function(){
  it("should return all ids", function(){
    $Filter = getFilter();
    $results = $Filter->filter()->ids();
    expect($results)->to_equal(array(0,1,2,3,4));

  });
});

describe("Traversing out from a filter with a predicate (printed.date=1780 )", function(){
  it("Should select all subjects with that predicate, and the existing filter set as objects", function(){
     $Filter = getFilter();
     $results = $Filter->filter(null,'date','1780')->traverseOut('printed')->ids();
     expect($results)->to_equal(array(4));
  });
});

//\pecs\run();

