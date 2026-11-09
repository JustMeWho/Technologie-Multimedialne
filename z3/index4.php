<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index4 - top - obciążenie serwera hostingowego (RAM, procesy itd.)</h3>
<?php
if (function_exists('exec')) {
    exec('TERM=xterm /usr/bin/top -n 1 -b -i', $top, $error);
    if (count($top) > 0) {
        echo nl2br(htmlspecialchars(implode("\n", $top)));
    } else {
        if ($error) {
            exec('TERM=xterm /usr/bin/top -n 1 -b 2>&1', $errout);
            echo "Error: " . htmlspecialchars(implode("\n", $errout));
        } else {
            echo "Polecenie top nie zwróciło danych (prawdopodobnie zablokowane na tym hostingu).";
        }
    }
} else {
    echo "Funkcja exec() jest niedostępna na tym hostingu (zablokowana ze względów bezpieczeństwa
          lub brak wykupionej usługi SSH).";
}
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
