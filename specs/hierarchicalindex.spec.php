<?php
require_once 'specs/spechelpers.php';
require_once 'lib/index.php';
require_once 'lib/hierarchicalindex.php';
require_once 'vendor/autoload.php';


function getStoreWithHierarchicalIndex($turtle){
  $store = getRafflesStore();
  $store->loadData($turtle);
  $store->createHierarchicalIndex();
  return $store;
}

describe("Hierarchical Index", function(){

  it("should block recursing paths", function(){
    $store = getRafflesStore();
    $index = $store->HierarchicalIndex;
    $index->indexPath('a','b','a');
    expect($index->paths_from['a']['b'])->to_equal('!');
    expect($index->links_to['a'])->to_equal(array());
  });

  it("should block recursing two-step paths", function(){
    $store = getRafflesStore();
    $index = $store->HierarchicalIndex;
    $index->indexPath('a','b','c');
    $index->indexPath('c','b','a');
    expect($index->paths_from['a']['b|b'])->to_equal('!');
    expect($index->links_to['a'])->to_equal(array('c'));
    expect($index->links_to['c'])->to_equal(array('a'));
  });

  it("should block recursing three-step paths", function(){
    $store = getRafflesStore();
    $index = $store->HierarchicalIndex;
    $index->indexPath('a','x','c');

    expect($index->links_to['c'])->to_equal(array('a'));

    $index->indexPath('c','y','d');
    
    $index->indexPath('d','z','a');
    expect($index->links_to['a'])->to_equal(array('d','c'));
    expect($index->links_to['c'])->to_equal(array('a','d'));
    expect($index->links_to['d'])->to_equal(array('c','a'));
    expect($index->paths_from['a']['x|y|z'])->to_equal('!');
    expect($index->paths_from['c']['y|z'])->to_equal(array('a'));
    expect($index->paths_from['d']['z|x'])->to_equal(array('c'));
 
  });

  it("should remove existing paths when it finds recursion in a shorter path that starts the same", function(){
    $store = getRafflesStore();
    $index = $store->HierarchicalIndex;
    $index->indexPath('marj','v|r|g','vs');
    $index->indexPath('marj','v|r','marj');
    expect($index->paths_from['marj']['v|r|g'])->to_equal('!');
  });

  it("should create a longer path back to the start, when there is no recursion", function(){
     $store = getRafflesStore();
    $index = $store->HierarchicalIndex;
    $index->indexPath('marj','v','dk');

    expect($index->paths_from['marj']['v'])->to_equal(array('dk'));
    expect($index->links_to['dk'])->to_equal(array('marj'));

    $index->indexPath('dk','in','perthshire');

    expect($index->paths_from['dk']['in'])->to_equal(array('perthshire'));
    expect($index->paths_from['marj']['v|in'])->to_equal(array('perthshire'));
    expect($index->links_to['dk'])->to_equal(array('marj'));
    expect($index->links_to['perthshire'])->to_equal(array('dk','marj'));


    $index->indexPath('perthshire','in','scotland');

    expect($index->paths_from['perthshire']['in'])->to_equal(array('scotland'));
    expect($index->links_to['scotland'])->to_equal(array('perthshire','dk','marj'));
    expect($index->paths_from['marj']['v|in|in'])->to_equal(array('scotland'));
    expect($index->paths_from['dk']['in|in'])->to_equal(array('scotland'));


    $index->indexPath('scotland','in','uk');
    expect($index->paths_from['marj']['v|in|in|in'])->to_equal(array('uk'));
  });



  describe("Given A -> B -> C  -> D", function(){

    it("Should Index A [B,C,D] ", function(){
      $turtle = <<<_TTL_
@base <http://example.com/> .
@prefix : <> .
<c> <knew> <d> .
<a> <wrote> <b> .
<b> <printer> <c> .
_TTL_;
      $store = getStoreWithHierarchicalIndex($turtle);
      $ex = 'http://example.com/';
      $actual = array_keys($store->related($ex.'a'));
      sort($actual);
      expect($actual)->to_equal(array($ex.'b', $ex.'c', $ex.'d'));
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
      expect(array_keys($store->related($ex.'b')))->to_equal(array( $ex.'c'));
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
      expect($store->related($ex.'c'))->to_equal(array());
    });

  });

    describe("Given the Marjorie/Dunkeld example", function(){

    it("Should Index M [E2,D,P] ", function(){
      $turtle = <<<_TTL_
@base <http://example.com/> .

<perthshire> <contains> <dunkeld> , <glenlednock> 
      ; <in> <scotland> .
<dunkeld> <route> <theend> , <marjorie> 
      ; <in> <perthshire>.

<glenlednock> <in> <perthshire> .
<glenlednock> <route> <greatcrack> .

<scotland> <in> <uk> .

<marjorie> <venue> <dunkeld> 
  ; <grade> <e2> .

<theend> <grade> <vs> .
_TTL_;
      $store = getStoreWithHierarchicalIndex($turtle);
      $ex = 'http://example.com/';
      $actual = array_keys($store->related($ex.'marjorie'));
      sort($actual);
      expect($actual)->to_equal(array($ex.'dunkeld', $ex.'e2', $ex.'perthshire', $ex.'scotland', $ex.'uk'));

      $dunkeld_expected = array($ex.'marjorie', $ex.'theend', $ex.'vs', $ex.'e2', $ex.'perthshire', $ex.'scotland', $ex.'uk');
      sort($dunkeld_expected);
      $dunkeld_actual = array_keys($store->related($ex.'dunkeld'));
      sort($dunkeld_actual);
//      var_dump($actual, $dunkeld_actual);
//      var_dump($store->HierarchicalIndex);
      expect($dunkeld_actual)->to_equal($dunkeld_expected);
    });
 
  });

 

});
\pecs\run();
?>
