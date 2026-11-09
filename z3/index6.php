<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index6 - dns_get_record() - rekord DNS podanej nazwy domenowej</h3>
<?php
/* Wyswietlenie danych zwiazanych z rekordem DNS podanej nazwy domenowej */
$result = @dns_get_record("pbs.edu.pl");
if ($result !== false && is_array($result)) {
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} else {
    echo "Funkcja dns_get_record() niedostępna lub brak rekordu (może być zablokowana na hostingu).";
}
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
