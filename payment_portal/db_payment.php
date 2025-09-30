<?php
$host = "localhost";
$port = 3307;
$dbname = "digital_payment";
$user = "root";
$pass = "";

try {
    $paymentDb = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $paymentDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Payment DB Connection failed: " . $e->getMessage());
}
?>
