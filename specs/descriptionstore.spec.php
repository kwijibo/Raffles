<?php

require_once 'specs/spechelpers.php';

require_once 'lib/descriptionstore.php';
require_once 'vendor/autoload.php';

describe("DescriptionStore", function(){
  
  it("should return zero for the size of an empty store", function(){
    $store = getStore();
    expect($store->size())->to_equal(0);
  });

  it("should let you insert descriptions", function(){
    $store = getStore();
    $ex = 'http://example.com/';
    $descriptions = array(
      $ex.'a' => array($ex.'name' => array("James")),
      $ex.'b' => array($ex.'name' => array("Angela")),
      $ex.'c' => array($ex.'name' => array("Dexter")),
    );
    $lineNumbers = $store->insertDescriptions($descriptions);
    expect($store->size())->to_equal(3);
    expect($lineNumbers)->to_equal(array(
      $ex.'a' => 0,
      $ex.'b' => 1,
      $ex.'c' => 2,
    ));

    $more_descriptions = array(
      $ex.'d' => array($ex.'name' => array("Delilah")),
      $ex.'e' => array($ex.'name' => array("Samson")),
      $ex.'f' => array($ex.'name' => array("Jake")),
    );

    $lineNumbers = $store->insertDescriptions($more_descriptions);
    
    expect($lineNumbers)->to_equal(array(
      $ex.'d' => 3,
      $ex.'e' => 4,
      $ex.'f' => 5,
    ));



  });
  it("should let you get a description by ids", function(){
    $store = getStore();
    $ex = 'http://example.com/';
    $description = array($ex.'id' => array($ex.'name' => array("James")));
    $lineNumbers = $store->insertDescriptions($description);
    $actual = $store->getDescriptionsByIDs($lineNumbers[$ex.'id']);
    expect($actual)->to_equal($description); 
  });

  it("should let you replace descriptions", function(){
    $store = getStore();
    $ex = 'http://example.com/';
    $description = array($ex.'id' => array($ex.'name' => array("James")));
    $lineNumbers = $store->insertDescriptions($description);
    $id = $lineNumbers[$ex.'id'];
    $newdescription = array($ex.'id' => array($ex.'name' => array("Jamie")));
    $replacement = array( $id => $newdescription);
    $store->replaceDescriptions($replacement);
    $actual = $store->getDescriptionsByIDs(array($id));
    expect($actual)->to_equal($newdescription);

  });

});

//\pecs\run();

