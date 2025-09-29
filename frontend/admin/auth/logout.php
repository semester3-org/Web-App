<?php
session_start();
session_destroy();
header("Location: ../../auth/login.php?type=admin&message=Anda telah logout");
exit();
