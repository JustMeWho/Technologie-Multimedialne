<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
start_session();
session_unset();
session_destroy();
header('Location: login.php');
exit();
?>
