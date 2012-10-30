<?php
namespace Raffles;
class Trie {

  var $_tree = array();
  const SPLITS = "\n\t\r ,.!?\"()";

  function insert($word, $value){
    $word_length = strlen($word);
    $index =& $this->_tree;
    for ($i = 0; $i < $word_length; $i++) {
      $char = $word[$i];
      if(!isset($index[$char])){
        $index[$char] = array();
      }
      if(($i+1)==$word_length){
        $index[$char]['_!'][] = $value;
        $index[$char]['_!']= array_unique($index[$char]['_!']);
      } else {
        $index =& $index[$char];
      }
    }
  }

  function getAllChildren($prefix, $subindex){
    $words = array();
    foreach($subindex as $node_key => $childnodes){
      if($node_key=='_!'){
        $words[$prefix] = $childnodes;
      } else {
        $words = array_merge($words, $this->getAllChildren($prefix.$node_key, $childnodes) );
      }
    }
    return $words;
  }

  function find($word, $returnNearestMatch=true){
    $word = strtolower($word);
    $word_length = strlen($word);
    $index =$this->_tree;
    $builtWord = '';
    for ($i = 0; $i < $word_length; $i++) {
      $char = $word[$i];
      if(!isset($index[$char])){
        if(!$returnNearestMatch){
          return array($word => array());
        } else if(isset($index['_!'])){
          //return word so far
          return array( $builtWord => $index['_!'], $word => array()); 
        } else {
          //return longer words
          $words = $this->getAllChildren($builtWord, $index);
          $words[$word]=array();
          return $words;
        }
      } else {
        $builtWord.=$char;
        $index = $index[$char];
      }
    }
    if(isset($index['_!'])){
      $words = array();
      $words[$word] = $index['_!'];
      $otherwords = array_values($this->getAllChildren($builtWord, $index));
      foreach($otherwords as $ids){
        $words[$word] = array_merge($words[$word], $ids);
      }
      return $words;
    } else {
      // $words = ($returnNearestMatch)? $this->getAllChildren($builtWord, $index) : array();
      $words = array();
      $words[$word]=array();
      $otherwords = array_values($this->getAllChildren($builtWord, $index));
      foreach($otherwords as $ids){
        $words[$word] = array_merge($words[$word], $ids);
      }
      return array($word => $words[$word]);
    }
    
  }

  function search($searchText,$returnSimilar=true){
    $text = strtolower($searchText);
    $word = strtok($text, self::SPLITS);
    $results = array();

    $results = $this->find($word);

    while($word = strtok(self::SPLITS)){
      $results = array_merge($results,$this->find($word, $returnSimilar));
    }
    strtok('','');
    return $results;
  }

  function indexText($text, $val){
    $text = strtolower($text);
    $word = strtok($text, self::SPLITS);
    $pos = strpos($text,$word);
    $this->insert($word,$val);
    while($word = strtok(self::SPLITS)){
      $pos = strpos($text,$word);
      $this->insert($word,$val);
    }
    strtok('','');
  }


}
