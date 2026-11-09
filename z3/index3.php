<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index3 - whoami - identyfikacja użytkownika serwera hostingowego</h3>
<?php
/* Skrypt zwykle wyswietla poprawnie zidentyfikowana nazwe uzytkownika hostingu,
   jesli uzytkownik ma wykupiona usluge SSH. Na budzetowych hostingach funkcja exec()
   bywa celowo zablokowana ze wzgledow bezpieczenstwa - wowczas wyswietlany jest komunikat. */
if (function_exists('exec')) {
    $user = @exec('whoami');
    if ($user) {
        echo "Zalogowany użytkownik serwera: " . htmlspecialchars($user);
    } else {
        echo "Funkcja exec() istnieje, ale polecenie 'whoami' nie zwróciło wyniku
              (prawdopodobnie zablokowane przez administratora hostingu).";
    }
} else {
    echo "Funkcja exec() jest niedostępna na tym hostingu (zablokowana ze względów bezpieczeństwa
          lub brak wykupionej usługi SSH).";
}
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
