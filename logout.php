<?php
// logout.php
require_once 'session.php';

// Padam semua data session
session_unset();
session_destroy();

// Redirect ke page login
header("Location: login.php");
exit();
?>