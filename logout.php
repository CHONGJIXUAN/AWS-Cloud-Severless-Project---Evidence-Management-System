<?php
session_start();
include 'includes/functions.php';

// Destroy the session
session_destroy();

// Redirect to home page
redirect('index.php');
?>
