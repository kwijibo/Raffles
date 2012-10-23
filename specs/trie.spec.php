<?php
require_once 'lib/trie.php';
require_once 'vendor/autoload.php';

describe("Trie Text Search Index", function(){

  it("should let you store a value against a key", function(){
    $trie = new \Raffles\Trie();
    $trie->insert('hello', 105);
    expect($trie->find('hello'))->to_equal(array('hello' => array(105)));
  });

  it("should let you add a value to a key", function(){
    $trie = new \Raffles\Trie();
    $trie->insert('hello', 4);
    $trie->insert('hello', 5);
    expect($trie->find('hello'))->to_equal(array('hello' => array(4,5)));
  });



  it("should index some text you pass it", function(){
    $trie = new \Raffles\Trie();
    $text = "Peter Piper Picked a Pack of Pickled Peppers so what's the pack of pickled peppers peter piper picked?";
    $trie->indexText($text, 5);
    expect($trie->find('peter'))->to_equal(array('peter'=>array(5)));
    // $text = file_get_contents('/Users/keithalexander/dev/conversions/ecco-18thc-texts/plainText/K106754.001.txt');
    // $trie->indexText($text, '01');
    // $ser = json_encode($trie->_tree);
    // var_dump(
    //   array(
    //     'serialised trie' => strlen($ser),
    //     'source text' => strlen($text),

    //   ));
    // echo $ser;
    
  });

  it("should let you search for æ", function(){
      $trie = new \Raffles\Trie();
      $trie->indexText('Romanæ Historiæ', 42);
      $trie->indexText('Romanü', 53);
      expect($trie->find('romanæ'))->to_equal(array('romanæ' => array(42)));
  });

  it("should return results for a multi-word search", function(){
    $trie = new \Raffles\Trie();
    $text = "Peter Piper Picked a Pack of Pickled Peppers so what's the pack of pickled peppers peter piper picked?";
    $trie->indexText($text, 5);
    expect($trie->search("pickled peppers"))->to_equal(array('pickled'=>array(5),'peppers'=>array(5)));
  });

  it("should return results for a similar word if the search word doesn't exist", function(){
    $trie = new \Raffles\Trie();
    $text = "Peter Piper Picked a Pack of Pickled Peppers so what's the pack of pickled peppers peter piper picked?";
    $trie->indexText($text, 5);
    expect($trie->search('pickles'))->to_equal(array('pickled'=>array(5),'pickles'=> array()));
  });

  it("should return longer words when the shorter word isn't indexed", function(){
    $trie = new \Raffles\Trie();
    $trie->indexText('searching', 42);
    expect($trie->find('search',true))->to_equal(array('searching' => array(42), 'search' => array()));
  });

  it("should return all words beginning with a prefix", function(){
    $trie = new \Raffles\Trie();
    $trie->indexText('searching', 42);
    $trie->indexText('searchable', 42);
    $trie->indexText('searchers', 42);
    $trie->indexText('searched', 42);
    $trie->indexText('another', 42);
    $actual = $trie->getAllChildren('search', $trie->_tree['s']['e']['a']['r']['c']['h']);
    $expected = array(
      'searching' => array(42),
      'searchable' => array(42),
      'searchers' => array(42),
      'searched' => array(42),
    );
    expect($actual)->to_equal($expected);
  });

});

\pecs\run();
?>
