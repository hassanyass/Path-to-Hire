<!-- <?php

// session_start();
// $conn = mysqli_connect('localhost', 'root', '', 'PathToHire') or die('connection failed');

?> -->


<?php
// conn.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'PathToHire');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>