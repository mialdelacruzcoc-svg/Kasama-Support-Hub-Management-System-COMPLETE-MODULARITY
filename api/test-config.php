<?php
echo "1. Starting<br>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "2. Before config<br>";

require_once 'config.php';

echo "3. After config<br>";

echo "4. Session: ";
print_r($_SESSION);
?>