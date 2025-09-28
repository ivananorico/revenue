<?php
$host = "localhost";
$port = 3307; // your MySQL port from XAMPP
$dbname = "market";
$user = "root"; // or your MySQL username
$pass = "";     // your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
