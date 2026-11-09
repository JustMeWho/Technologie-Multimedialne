<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */ ?>
<?php
session_start();
$_SESSION = array();
session_destroy();
header("location: index.php");
exit;
?>
