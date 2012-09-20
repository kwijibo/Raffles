<?php
  require_once 'vendor/autoload.php';
  require_once 'lib/rafflesstore.php';


  $options = getopt('s:q:p::');
  $storename = $options['s'];
  $query = $options['q'];
  $property = $options['p'];
  $store = new RafflesStore($storename);
  $results = $store->search($query,$property);
  foreach($results as $uri => $props){
    echo("\n<{$uri}> ");
    foreach($props as $p => $obs){
      echo("\n\t<{$p}> \t");
      foreach($obs as $o){
        switch($o['type']){
          case 'uri': 
            echo("<{$o['value']}> , ");
            break;
          case 'literal': 
            echo("\"{$o['value']}\" , ");
            break;
        }
      }
      echo " ; ";
    }
     echo " . \n\n\n";
  }
   echo "\n----------------\n";
  echo count(array_keys($results)) . " results \n\n ";
?>
