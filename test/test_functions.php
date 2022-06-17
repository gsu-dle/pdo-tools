<?php

declare(strict_types=1);

use GAState\Tools\pdoDB\pdoDB;

require __DIR__ . '/../vendor/autoload.php';

$pdo = new pdoDB(
    type: 'mysql',
    hostname: '127.0.0.1',
    username: 'root',
    password: 'root',
    database: 'test',
    port: 3306,
    tls: TRUE,
    key:  './client-key.pem',
    certificate:  './client-cert.pem',
    cacert:  './ca-cert.pem'
);

$create_test_table = $pdo->query(
    "CREATE TABLE if not exists `test` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `firstname` varchar(200) DEFAULT NULL,
    `lastname` varchar(200) DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;");

$insert   =  $pdo->query("INSERT INTO test(firstname,lastname) VALUES(:f,:l)", array("f"=>"Jeb","l"=>"Barger"));
echo $insert;


// Fetch whole table
$persons = $pdo->query("SELECT * FROM test LIMIT 1000");
print_r($persons);

// 1. Read friendly method  
$pdo->bind("FirstName","Jeb");
$pdo->bind("LastName","Barger");
$person   =  $pdo->query("SELECT * FROM test WHERE firstname = :FirstName AND lastname = :LastName");
print_r($person);

// 2. Bind more parameters
$pdo->bindMore(array("FirstName"=>"Jeb","LastName"=>"Barger"));
$person   =  $pdo->query("SELECT * FROM test WHERE firstname = :FirstName AND lastname = :LastName");
print_r($person);

// 3. Or just give the parameters to the method
$person   =  $pdo->query("SELECT * FROM test WHERE firstname = :FirstName",array("FirstName"=>"Jeb"));
print_r($person);

// Fetch a row
$person     =  $pdo->row("SELECT * FROM test WHERE  firstname = :FirstName", array("FirstName"=>"Jeb"));
print_r($person);

// Fetch one single value
$pdo->bind("FirstName","Jeb");
$firstname = $pdo->single("SELECT firstname FROM test WHERE firstname = :FirstName");
print_r($firstname);

// // Delete - returns number of rows
$delete   =  $pdo->query("DELETE FROM test WHERE Id = :id", array("id"=>"1"));
echo $delete;

// Update - returns number of rows
$update   =  $pdo->query("UPDATE test SET firstname = :f WHERE Id = :id", array("f"=>"Jeb","id"=>"1"));
echo $update;

// Insert - returns number of rows
$insert   =  $pdo->query("INSERT INTO test(firstname,lastname) VALUES(:f,:l)", array("f"=>"Vivek","l"=>"test"));
echo $insert;

// Fetch style as third parameter - More info about the PDO fetchstyle : http://php.net/manual/en/pdostatement.fetch.php
$person_num =     $pdo->row("SELECT * FROM test WHERE firstname = :FirstName", array("FirstName"=>"Jeb"), PDO::FETCH_NUM);
print_r($person_num);