<?php
require_once 'lib/index.php';
require_once 'lib/hierarchicalindex.php';
require_once 'vendor/autoload.php';

require_once 'specs/spechelpers.php';

function getStoreWithHierarchicalIndex($turtle){
  $store = getRafflesStore();
  $store->loadData($turtle);
  $store->createHierarchicalIndex();
  return $store;
}

describe("Hierarchical Index", function(){
  describe("Given A -> B -> C ", function(){

    it("Should Index A [B,C] ", function(){
      $turtle = <<<_TTL_
@base <http://example.com/> .
@prefix : <> .
<a> <wrote> <b> .
<b> <printer> <c> .
_TTL_;
      $store = getStoreWithHierarchicalIndex($turtle);
      $ex = 'http://example.com/';
      $actual = $store->relatedUris($ex.'a');
      sort($actual);
      expect($actual)->to_equal(array($ex.'b', $ex.'c'));
    });
    
    it("Should Index B [C] ", function(){
      $turtle = <<<_TTL_
@base <http://example.com/> .
@prefix : <> .

<a> <wrote> <b> .
<b> <printer> <c> .
_TTL_;
      $store = getStoreWithHierarchicalIndex($turtle);
      $ex = 'http://example.com/';
      expect($store->relatedUris($ex.'b'))->to_equal(array( $ex.'c'));
    });
    
    it("Should Index C [] ", function(){
      $turtle = <<<_TTL_
@base <http://example.com/> .
@prefix : <> .

<a> <wrote> <b> .
<b> <printer> <c> .
_TTL_;
      $store = getStoreWithHierarchicalIndex($turtle);
      $ex = 'http://example.com/';
      expect($store->relatedUris($ex.'c'))->to_equal(array());
    });

  });

    describe("Given the Marjorie/Dunkeld example", function(){

    it("Should Index M [E2,D,P] ", function(){
      $turtle = <<<_TTL_
@base <http://example.com/> .
<perthshire> <contains> <dunkeld> , <glenlednock> ; <in> <scotland> .
<dunkeld> <route> <theend> , <marjorie> ; <in> <perthshire>.
<glenlednock> <in> <perthshire> .
<glenlednock> <route> <greatcrack> .
<scotland> <in> <uk> .

<marjorie> <venue> <dunkeld> ; <grade> <e2> .
<theend> <grade> <vs> .
_TTL_;
      $store = getStoreWithHierarchicalIndex($turtle);
      $ex = 'http://example.com/';
      $actual = $store->relatedUris($ex.'marjorie');
      sort($actual);
      print_r($store->Index->_related_from);
      expect($actual)->to_equal(array($ex.'dunkeld', $ex.'e2', $ex.'perthshire', $ex.'scotland', $ex.'uk'));

      $dunkeld_expected = array($ex.'marjorie', $ex.'theend', $ex.'vs', $ex.'e2', $ex.'perthshire', $ex.'scotland', $ex.'uk');
      sort($dunkeld_expected);
      $dunkeld_actual = $store->relatedUris($ex.'dunkeld');
      sort($dunkeld_actual);
      expect($dunkeld_actual)->to_equal($dunkeld_expected);
    });
 
  });


});
//\pecs\run();
?>
