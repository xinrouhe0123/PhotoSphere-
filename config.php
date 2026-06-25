<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "gw02";
$port = 3307;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>