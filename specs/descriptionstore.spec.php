<?php

require_once 'lib/descriptionstore.php';
require_once 'vendor/autoload.php';

require_once 'specs/spechelpers.php';
describe("DescriptionStore", function(){
  
  it("should return zero for the size of an empty store", function(){
    $store = getStore();
    expect($store->size())->to_equal(0);
  });

  it("should let you insert a description", function(){
    $store = getStore();
    $ex = 'http://example.com/';
    $description = array($ex.'id' => array($ex.'name' => array("James")));
    $lineNumber = $store->insertDescription($description);
    $lineNumber = $store->insertDescription($description);
    $lineNumber = $store->insertDescription($description);
    expect($store->size())->to_equal(3);
    expect($lineNumber)->to_equal(2);

  });
  it("should let you get a description by ids", function(){
    $store = getStore();
    $ex = 'http://example.com/';
    $description = array($ex.'id' => array($ex.'name' => array("James")));
    $lineNumber = $store->insertDescription($description);
    var_dump($lineNumber);
    $actual = $store->getDescriptionsByIDs($lineNumber);
    expect($actual)->to_equal($description); 
  });


});

//\pecs\run();

