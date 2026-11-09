<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index5 - ls -al - lista plików z prawami w katalogu tego zadania</h3>
<?php
/* Na tym hostingu funkcja shell_exec jest calkowicie usunieta (undefined) */
$output = function_exists('shell_exec') ? @shell_exec('ls -al') : false;
if ($output) {
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} else {
    /* Fallback: gdy shell_exec jest zablokowane, prawa i liste plikow
       mozna wyswietlic wlasnymi srodkami PHP */
    echo "<p>shell_exec('ls -al') niedostępne - lista plików wygenerowana funkcjami PHP:</p>";
    echo "<pre>";
    $pliki = @scandir('.');
    if ($pliki !== false) {
        foreach ($pliki as $plik) {
            if ($plik === '.' || $plik === '..') { continue; }
            $prawa = substr(sprintf('%o', @fileperms($plik) ?: 0), -4);
            $rozmiar = @filesize($plik) ?: 0;
            echo str_pad($prawa, 6) . str_pad((string)$rozmiar, 10) . htmlspecialchars($plik) . "\n";
        }
    }
    echo "</pre>";
}
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
