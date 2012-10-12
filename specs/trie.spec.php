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
    expect($trie->search('pickles'))->to_equal(array('pickled'=>array(5),'pickles'=> false));
  });

});

\pecs\run();
?>
