<?php

$host = "localhost";
$username = "GW02";
$password = "PhotoSphere";
$database = "gw02";
$port = 3306;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>