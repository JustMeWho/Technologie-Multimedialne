<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index7 - gethostbyname(), REMOTE_ADDR, gethostbyaddr()</h3>
<?php
/* Hosting 7m.pl dziala za proxy - REMOTE_ADDR ma wartosc 127.0.0.1,
   prawdziwe IP goscia portalu jest w naglowkach X-Real-IP / X-Forwarded-For. */
function ip_goscia(): string {
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return trim($_SERVER['HTTP_X_REAL_IP']);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* Wyświetlenie IP serwera związanego z podaną nazwą domenową */
$ip_dom = gethostbyname('pbs.edu.pl');
echo "gethostbyname('pbs.edu.pl'): " . htmlspecialchars($ip_dom) . "<br>";

/* Wyświetlenie IP routera umozliwiajacego wizyte na tej stronie (IP goscia portalu) */
$ip = ip_goscia();
echo "IP gościa portalu (X-Real-IP/X-Forwarded-For): " . htmlspecialchars($ip) . "<br>";
echo "REMOTE_ADDR widziany przez PHP (za proxy): " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') . "<br>";

/* Wyświetlenie nazwy domenowej skojarzonej z podanym IP */
$hostname = @gethostbyaddr("8.8.8.8");
echo "gethostbyaddr('8.8.8.8'): " . htmlspecialchars($hostname !== '8.8.8.8' ? $hostname : 'brak') . "<br>";

/* Wyświetlenie nazwy domenowej routera umozliwiajacego wizyte na tej stronie */
$hostname_gosc = @gethostbyaddr($ip);
echo "gethostbyaddr(IP gościa): " . htmlspecialchars($hostname_gosc) . "<br>";
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
