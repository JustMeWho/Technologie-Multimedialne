<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index1 - Netstat PHP (uproszczona funkcjonalność aplikacji netstat)</h3>
<?php
/* Uproszczona wersja klasycznej aplikacji netstat (Windows/Linux/macOS),
   inspirowana skryptem netstat.php: https://github.com/netstat-php/netstat_php
   Hosting 7m.pl to serwer z rodziny UNIX - tabele polaczen odczytywane sa z /proc/net/tcp. */

function dekoduj_adres(string $hex): string {
    /* adres w /proc/net/tcp to zakodowana odwrotnie (little-endian) liczba hex */
    $ip = @long2ip((int)hexdec($hex));
    return $ip ?: $hex;
}

function netstat_proc(): array {
    $wynik = array();
    foreach (array("/proc/net/tcp", "/proc/net/tcp6") as $plik) {
        if (!@is_readable($plik)) { continue; }
        $linie = @file($plik, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linie === false) { continue; }
        foreach ($linie as $i => $linia) {
            if ($i === 0) { continue; }   // pominiecie naglowka
            $kolumny = preg_split('/\s+/', trim($linia));
            if (count($kolumny) < 4) { continue; }
            $local = $kolumny[1];         // adres:port nasluchujacy
            $stan  = $kolumny[3];         // 0A = LISTEN
            list($adresHex, $portHex) = explode(':', $local);
            $port = hexdec($portHex);
            if (strtoupper($stan) === '0A') {
                $wynik[] = array('adres' => dekoduj_adres($adresHex), 'port' => $port);
            }
        }
    }
    return $wynik;
}

/* Rozbudowa programu o wykrywanie dzialania innych usług - nazwy znanych portów */
$uslugi = array(
    21 => 'FTP', 22 => 'SSH', 25 => 'SMTP', 53 => 'DNS', 80 => 'HTTP',
    110 => 'POP3', 143 => 'IMAP', 443 => 'HTTPS', 465 => 'SMTPS',
    587 => 'Submission', 993 => 'IMAPS', 995 => 'POP3S', 3306 => 'MySQL',
);

$sluchajace = netstat_proc();

if (count($sluchajace) > 0) {
    echo "<table border='1' cellpadding='4'><tr><th>Adres lokalny</th><th>Port</th><th>Usługa</th><th>Stan</th></tr>";
    foreach ($sluchajace as $w) {
        $nazwa = isset($uslugi[$w['port']]) ? $uslugi[$w['port']] : 'nieznana';
        echo "<tr><td>" . htmlspecialchars($w['adres']) . "</td><td>" . $w['port']
           . "</td><td>" . htmlspecialchars($nazwa) . "</td><td>LISTEN</td></tr>";
    }
    echo "</table>";
} else {
    /* Fallback: proba uzycia klasycznego polecenia netstat przez shell_exec.
       Na tym hostingu funkcja shell_exec jest calkowicie usunieta (undefined). */
    $output = function_exists('shell_exec') ? @shell_exec('netstat -tuln 2>&1') : false;
    if ($output) {
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p>Nie udało się odczytać tabeli połączeń (dostęp do /proc/net/tcp zablokowany
              oraz polecenie netstat niedostępne na tym hostingu).</p>";
    }
}

/* Wniosek: niektóre usługi (np. serwer pocztowy hostingu) działają na innych maszynach
   niż serwer WWW, więc nie są widoczne w tabeli nasłuchiwania tego serwera. */
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
