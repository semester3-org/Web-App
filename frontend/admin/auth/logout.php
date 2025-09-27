<?php
session_start();
session_destroy();
header("Location: login.php?type=admin&message=Anda telah logout");
exit();
