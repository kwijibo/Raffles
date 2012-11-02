Raffles is a simple file-based RDF/Graph datastore written in PHP. It is
written as a way to dynamically publish small datasets easily on PHP web
hosting without using an external database.


## Installation 



composer create-project kwijibo/raffles raffles

see [Composer](http://getcomposer.org)

## Creating a Store and Importing Data


        require 'Raffles/lib/rafflesstore.php';
        require_once 'Raffles/vendor/autoload.php';

        $data_dir = __DIR__ . '/data';
        $store = new RafflesStore($data_dir);
        $store->indexPredicates = array(
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
            'http://purl.org/dc/terms/creator',
            'http://purl.org/dc/terms/date',
        );
        // you can configure Raffles to only index specific predicates

        $store->addNamespacePrefix('library', 'http://purl.org/library/');
        // add prefixes you want to use in queries


### Getting types of things in the Store

        $types = $store->getTypes();
        // array(
        // http://schema.org/Place => 321,
        // http://schema.org/Person => 456,
        //)



### Getting Facets

        $dates = $store->getFacets('http://purl.org/dc/terms/date');

        // array ( "1560" => 4, "1562" => 12, "1570" => 31  )


## Basic Path query language

`rdf:type=foaf:Person` (all things of type Person)

`foaf:made/dct:date=1780` (anyone who made something in 1780)

        
        $limit=20;
        $offset=0;
        $results = $store->query("foaf:made/dct:date=1560", $limit, $offset);
        

Results are returned as a PHP associative array following the [RDF JSON](https://github.com/iand/rdf-json) structure

        
        array (
        S => array(
            P => array (  
              array(
                  value => O, 
                  type => literal|uri|bnode 
                  [, lang=O_LANG ] 
                  [, datatype=O_DATATYPE ]
                )
            )
         )
        

## Search

        $results = $store->search("Edinbu");

# Running the tests

`php specs/run.php`

### License

This code is Public Domain.
Use, copy, or change it freely, at your own risk.

