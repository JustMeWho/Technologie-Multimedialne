<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>125340</title>
</head>
<body>
<h3>index8 - Geolokalizacja gościa portalu (ipinfo.io, format JSON)</h3>
<?php
/* Hosting 7m.pl dziala za proxy - REMOTE_ADDR = 127.0.0.1,
   prawdziwe IP goscia portalu jest w naglowkach X-Real-IP / X-Forwarded-For. */
function ip_goscia(): string {
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return trim($_SERVER['HTTP_X_REAL_IP']);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* Przyblizona geolokalizacja goscia portalu - dane pozyskiwane w formacie JSON.
   Uwaga: informacja geolokalizacyjna moze byc celowo sfalszowana lub nieprecyzyjna
   (prywatnosc klientow dostawcow uslug, VPN itd.). */
function ip_details(string $ip): ?object {
    $context = @stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents("http://ipinfo.io/{$ip}/geo", false, $context);
    if ($json === false) {
        return null;
    }
    return json_decode($json);
}

$ipaddress = ip_goscia();
$details = ip_details($ipaddress);

if ($details !== null) {
    echo "IP: " . htmlspecialchars($details->ip ?? $ipaddress) . "<br>";
    echo "Country: " . htmlspecialchars($details->country ?? '') . "<br>";
    echo "Region: " . htmlspecialchars($details->region ?? '') . "<br>";
    echo "City: " . htmlspecialchars($details->city ?? '') . "<br>";
    echo "Loc: " . htmlspecialchars($details->loc ?? '') . "<br>";

    /* Wklej do Google maps wyswietlone wspolrzedne i zweryfikuj,
       czy odpowiadaja miejscowosci wymienionej przez skrypt */
    if (!empty($details->loc)) {
        echo "<a href='https://www.google.pl/maps/place/" . urlencode($details->loc)
           . "' target='_blank'>LINK - pokaż na Google Maps</a><br>";
    }
} else {
    echo "Brak danych geolokalizacyjnych (serwis ipinfo.io niedostępny).";
}
?>
<br><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a>
</body>
</html>
