<?php
session_start();
session_destroy();

// Redirect ke login umum
header("Location: index.php?message=Anda telah logout");
exit();
