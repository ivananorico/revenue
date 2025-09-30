<?php
$host = "localhost";
$port = 3307;
$dbname = "market";
$user = "root";
$pass = "";

try {
    $marketDb = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $marketDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Market DB Connection failed: " . $e->getMessage());
}
?>
