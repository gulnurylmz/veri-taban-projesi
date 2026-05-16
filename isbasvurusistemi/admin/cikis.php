<?php
// admin/cikis.php — Admin oturumu kapat
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
