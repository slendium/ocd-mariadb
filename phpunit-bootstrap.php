<?php

require_once 'vendor/autoload.php';

// should declare the following constants:
// * MARIA_HOST
// * MARIA_DBNAME
// * MARIA_USER
// * MARIA_PASS
require_once 'phpunit-dbconfig.php';

$host = MARIA_HOST;
$database = MARIA_DBNAME;
$username = MARIA_USER;
$password = MARIA_PASS;
$pdo = PDO::connect("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);

$stmt = $pdo->prepare('select count(1) as count from information_schema.tables where TABLE_SCHEMA = :db');
$stmt->execute([ 'db' => $database ]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result['count'] != 0) {
	throw new Exception("Expected test database `$database` to be empty, but it contained {$result['count']} table(s)");
}
