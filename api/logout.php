<?php
session_start(); // Sugdan ang session
session_unset(); // Tangtangon tanang session variables
session_destroy(); // I-destroy ang session sa server

// Mogawas sa api folder padulong sa index.php
header("Location: ../index.php"); 
exit();
?>