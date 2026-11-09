<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */ ?>
<?php
session_start();   // zapewnia dostęp do zmiennych sesyjnych w danym pliku

/* Weryfikacja, czy użytkownik jest zalogowany.
   Jeśli nie - eliminacja wejścia bez autoryzacji: przekierowanie do index3.php w celu zalogowania. */
if (!isset($_SESSION['loggedin'])) {
    header('Location: index3.php');
    exit();
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>125340</title>
</head>
<body>
Jestem zalogowany<br>
<br><a href="logout.php">Wylogowanie</a> | <a href="index.php">&larr; Powrót do z1</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
