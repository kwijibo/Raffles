<?php

require_once 'lib/indexfilter.php';
require_once 'lib/index.php';
require_once 'vendor/autoload.php';

function getFilter(){
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
    $Index = new Index();
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
    $Filter = new IndexFilter($Index);

    return $Filter;
}

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

\pecs\run();

