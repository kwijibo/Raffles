<?php 
require_once 'specs/spechelpers.php';

require_once 'lib/index.php';
require_once 'vendor/autoload.php';


describe("Index", function(){
  it("should let you add an entry", function(){
    $index = getIndex();
    $index->addSubject(testUri, 42);
    expect($index->getSubject(testUri))->to_equal(42);
  });
  it("should let you add numeric literals as objects", function(){
    $index = getIndex();
    $lineNumbers = array(0,4,59);
    $index->addPredicateObject('date','1800', $lineNumbers);
    expect($index->getObject('1800'))->to_equal($lineNumbers);
  });

  it("should let you replace a subject", function(){
    $index = getIndex();
    $index->addSubject('urn:foo', 1);
    $index->replaceSubject('urn:foo', 5);
    expect($index->getSubject('urn:foo'))->to_equal(5);
  });

  it("should return a subject URI by ID", function(){
    $index = getIndex();
    $index->addSubject('urn:foo', 42);
    expect($index->getSubjectByID(42))->to_equal('urn:foo');


  });

  it("should let you remove an entry", function(){});

  it("should let you search for an object of a given predicate", function(){
      $index = getIndex();
      $index->addPredicateObject('date', '1500', 1);
      $index->addPredicateObject('date', '1550', 2);
      $index->addPredicateObject('date', '1066', 3);
      expect($index->searchObject( '15', 'date'))->to_equal(array(1,2));
  });

  /*
    commented out because it uses the SearchTrie, which is currently built independently 
    of addPredicateObject
    
    it("should let you search for an object of all predicates", function(){
      $index = getIndex();
      $index->addPredicateObject('name', 'John Paul', 1);
      $index->addPredicateObject('surname', 'Paul', 2);
      $index->addPredicateObject('firstname', 'John', 3);
      expect($index->searchObject( 'Paul'))->to_equal(array(1,2));
    });

   */

  it("should let you getAll IDs in the index", function(){
    $index = getIndex();
   $index->addSubject('a', 1); 
   $index->addSubject('b', 34); 
   $index->addSubject('c', 9); 
   expect($index->getAll())->to_equal(array(1,34,9));
  });

  it("should give you subject namespaces", function(){
    $index = getIndex();
    $ex = 'http://example.com/id/';
   $index->addSubject($ex.'a', 1); 
   $index->addSubject($ex.'b', 34); 
   $index->addSubject($ex.'c', 9); 
   expect($index->getSubjectNamespaces())->to_equal(array($ex));

  });

  it("should give you subject namespaces", function(){
    $index = getIndex();
    $ex = 'http://example.com/id/';
   $index->addSubject($ex.'a', 1); 
   $index->addSubject($ex.'b', 34); 
   $index->addSubject($ex.'c', 9); 
   expect($index->getSubjectNamespaces())->to_equal(array($ex));

  });

  describe("getVocabularyNamespaces", function(){
    it("should return namespaces used in properties", function(){
          $index = getIndex();
    $ex = 'http://example.com/terms/';
   $index->addPredicateObject($ex.'a', 'foo', 1); 
   $index->addPredicateObject($ex.'b', 'foo', 1); 
   $index->addPredicateObject($ex.'c', 'foo', 1); 
   $index->addPredicateObject($ex.'v2/a', 'foo',1); 
   expect($index->getVocabularyNamespaces())->to_equal(array($ex, $ex.'v2/'));

    });
  });



  it("should return results from a triple pattern query", function(){
    $index = getRafflesStore(true)->Index;
    $triplepattern = getTriplePatternArray();
    $actual = $index->query($triplepattern);
    expect($actual)->to_equal(array(37)); //Alexander Pope
  });

  describe("Facets", function(){

    it("should filtered by a set of ids to the intersection", function(){
      $store = getRafflesStore();
      $exns = 'http://example.com/';
      $data = <<<_TTL_
@base <{$exns}> .
<A> a <Book> ; <date> "1500" .
<C> a <Book> ; <date> "1500" .
<B> a <Person> ; <born> "1450" .
_TTL_;
      $store->loadData($data);
      $index = $store->Index;
      $ids = $index->getPredicateObject(rdftype, $exns.'Book');
      $filtered = $index->filterPredicateObjectIndex($ids);
      $expected = array(
        rdftype =>      array(   $exns.'Book' => array(0,1) ),
        $exns.'date' => array(  '1500'=> array(0,1)   )
      );
        
      expect($filtered)->to_equal($expected);
    });

  });

  describe("splitURI", function(){
    it("should return an array( namespace, localname)", function(){
      $index = getIndex();
      expect($index->splitURI('http://example.com/foo/bar'))->to_equal(array('http://example.com/foo/', 'bar'));
      expect($index->splitURI('http://example.com/foo/bar/'))->to_equal(array('http://example.com/foo/', 'bar/'));
      expect($index->splitURI('http://example.com/foo#bar'))->to_equal(array('http://example.com/foo#', 'bar'));
      expect($index->splitURI('http://example.com/foo/bar#'))->to_equal(array('http://example.com/foo/', 'bar#'));
      expect($index->splitURI('http://example.com/ns/people/tom'))->to_equal(array('http://example.com/ns/people/', 'tom'));
    });
  });

});


 //\pecs\run();
?>
