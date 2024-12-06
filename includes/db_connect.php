<?php
$host = '127.0.0.1';
$dbname = 'hospital_management_system';
$username = 'noman';
$password = 'noman';
$base_url = 'http://127.0.0.1/Hospital_Management_System/';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}
?> 