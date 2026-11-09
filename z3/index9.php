<?php declare(strict_types=1); ?>
<?php
require_once "db_config.php";

/* Hosting 7m.pl dziala za proxy - REMOTE_ADDR = 127.0.0.1,
   prawdziwe IP goscia portalu jest w naglowkach X-Real-IP / X-Forwarded-For. */
function ip_goscia(): string {
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return trim($_SERVER['HTTP_X_REAL_IP']);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* Geolokalizacja IP - dane pozyskiwane w formacie JSON z serwisu ipinfo.io */
function ip_details(string $ip): ?object {
    $context = @stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents("http://ipinfo.io/{$ip}/geo", false, $context);
    if ($json === false) {
        return null;
    }
    return json_decode($json);
}

/* Przefiltrowanie danych dotyczacych przegladarki - wyluskanie tylko uzytecznych
   i wiarygodnych danych (punkt 15). Na hostingu z funkcja get_browser() korzystamy z niej,
   w przeciwnym razie parsujemy naglowek HTTP_USER_AGENT. */
function przegladarka_goscia(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'nieznana';
    if (function_exists('get_browser')) {
        $b = @get_browser(null, true);
        if (is_array($b) && !empty($b['browser'])) {
            return trim($b['browser'] . ' ' . ($b['version'] ?? ''));
        }
    }
    /* Fallback - proste rozpoznanie przegladarki z naglowka User-Agent */
    if (preg_match('/Edg\/([0-9.]+)/', $ua, $m))     return 'Edge ' . $m[1];
    if (preg_match('/OPR\/([0-9.]+)/', $ua, $m))     return 'Opera ' . $m[1];
    if (preg_match('/Chrome\/([0-9.]+)/', $ua, $m))  return 'Chrome ' . $m[1];
    if (preg_match('/Firefox\/([0-9.]+)/', $ua, $m)) return 'Firefox ' . $m[1];
    if (preg_match('/Version\/([0-9.]+).*Safari/', $ua, $m)) return 'Safari ' . $m[1];
    return 'Inna (' . mb_substr($ua, 0, 60) . ')';
}

$ipaddress = ip_goscia();

/* Dane z punktu 16 - pozyskiwane po stronie klienta skryptem JS i przesylane metodą POST:
   rozdzielczość ekranu, rozdzielczość okna przeglądarki, ilość kolorów ekranu,
   zezwolenie na zapis ciasteczek, zezwolenie na aplety Javy, język przeglądarki. */
$screen_width  = (int)($_POST['screen_width']  ?? 0) ?: null;
$screen_height = (int)($_POST['screen_height'] ?? 0) ?: null;
$window_width  = (int)($_POST['window_width']  ?? 0) ?: null;
$window_height = (int)($_POST['window_height'] ?? 0) ?: null;
$color_depth   = (int)($_POST['color_depth']   ?? 0) ?: null;
$cookies_enabled = isset($_POST['cookies_enabled']) ? ($_POST['cookies_enabled'] === 'true' ? 1 : 0) : null;
$java_enabled    = isset($_POST['java_enabled'])   ? ($_POST['java_enabled'] === 'true' ? 1 : 0)   : null;
$language        = $_POST['language'] ?? null;

$browser = przegladarka_goscia();

/* PUNKT 12: zapis w bazie danych IP goscia odwiedzajacego portal
   oraz daty i godziny wejscia na strone (datetime) - wraz z danymi z pkt 15/16. */
$stmt = mysqli_prepare($conn,
    "INSERT INTO goscieportalu (ipaddress, datetime, browser, screen_width, screen_height, window_width, window_height, color_depth, cookies_enabled, java_enabled, language)
     VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssiiiiiiis", $ipaddress, $browser, $screen_width, $screen_height,
                           $window_width, $window_height, $color_depth, $cookies_enabled, $java_enabled, $language);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/* Wyswietlenie tabeli gości portalu */
$result = mysqli_query($conn, "SELECT * FROM goscieportalu ORDER BY datetime DESC LIMIT 100");

/* Geolokalizacja pozyskiwana na etapie wyswietlania tabeli - dla unikalnych IP gosci.
   Nie ma potrzeby zapisywac danych geolokalizacyjnych gościa w bazie danych:
   wystarczy zapisac sam IP, a na podstawie IP da się wyswietlic dane geolokalizacyjne. */
$geo_cache = array();
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_array($result)) {
    $ip = $row['ipaddress'];
    if (!isset($geo_cache[$ip])) {
        $geo_cache[$ip] = ip_details($ip);
    }
}
mysqli_data_seek($result, 0);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>125340</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    /* Skrypt JS pozyskujacy dane przegladarki goscia portalu (punkt 16):
       - rozdzielczosc ekranu: screen.width / screen.height
       - rozdzielczosc okna przegladarki: window.innerWidth / innerHeight
       - ilosc kolorow ekranu: screen.colorDepth
       - zezwolenie na zapis ciasteczek: navigator.cookieEnabled
       - zezwolenie na aplety Javy: navigator.javaEnabled()
       - jezyk przegladarki: navigator.language */
    function wyslijDaneKlienta() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        var pola = {
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            window_width: window.innerWidth || document.documentElement.clientWidth,
            window_height: window.innerHeight || document.documentElement.clientHeight,
            color_depth: window.screen.colorDepth,
            cookies_enabled: navigator.cookieEnabled,
            java_enabled: navigator.javaEnabled ? navigator.javaEnabled() : false,
            language: navigator.language || navigator.userLanguage || ''
        };
        for (var klucz in pola) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = klucz;
            input.value = pola[klucz];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
    /* Automatyczny zapis wizyty przy pierwszym wejsciu w danej sesji przegladarki.
       Uwaga: addEventListener zamiast window.onload - hosting 7m.pl wstrzykuje skrypt
       robot.js, ktory nadpisuje window.onload i kasowalby ten handler. */
    window.addEventListener('load', function() {
        try {
            if (!sessionStorage.getItem('wizyta_zalogowana')) {
                sessionStorage.setItem('wizyta_zalogowana', '1');
                wyslijDaneKlienta();
            }
        } catch (e) {
            wyslijDaneKlienta();
        }
    });
    </script>
</head>
<body>
    <div style="margin:10px;">
        <h3>Goście portalu - log wizyt (punkty 12-16)</h3>
        <p><a href="index.php">&larr; Powrót do z3</a> | <a href="../index.php">&larr; Powrót do listy zadań</a></p>
        <p>Twoje IP: <b><?php echo htmlspecialchars($ipaddress); ?></b>
           &nbsp;|&nbsp; Przeglądarka: <b><?php echo htmlspecialchars($browser); ?></b>
           &nbsp;|&nbsp; Język: <b><?php echo htmlspecialchars((string)$language); ?></b></p>
        <?php if (isset($geo_cache[$ipaddress]) && $geo_cache[$ipaddress] !== null): ?>
        <p>Geolokalizacja: <?php
            $d = $geo_cache[$ipaddress];
            echo htmlspecialchars(($d->city ?? '') . ', ' . ($d->region ?? '') . ', ' . ($d->country ?? '')
                . ' (' . ($d->loc ?? '') . ')');
        ?></p>
        <?php endif; ?>

        <table class="table table-striped table-bordered table-sm" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>IP</th>
                    <th>Data i godzina</th>
                    <th>Przeglądarka (pkt 15)</th>
                    <th>Miejscowość / kraj</th>
                    <th>Google Maps (pkt 13)</th>
                    <th>Ekran (pkt 16)</th>
                    <th>Okno przeglądarki (pkt 16)</th>
                    <th>Kolory (bity/px)</th>
                    <th>Ciasteczka</th>
                    <th>Java</th>
                    <th>Język</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_array($result)): ?>
                <?php $geo = $geo_cache[$row['ipaddress']] ?? null; ?>
                <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['ipaddress']); ?></td>
                    <td><?php echo htmlspecialchars($row['datetime']); ?></td>
                    <td><?php echo htmlspecialchars($row['browser'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(trim(($geo->city ?? '') . ', ' . ($geo->country ?? ''), ', ')); ?></td>
                    <td>
                        <?php if ($geo !== null && !empty($geo->loc)): ?>
                        <a href='https://www.google.pl/maps/place/<?php echo urlencode($geo->loc); ?>' target='_blank'>LINK</a>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><?php echo $row['screen_width'] !== null ? ((int)$row['screen_width'] . 'x' . (int)$row['screen_height']) : '-'; ?></td>
                    <td><?php echo $row['window_width'] !== null ? ((int)$row['window_width'] . 'x' . (int)$row['window_height']) : '-'; ?></td>
                    <td><?php echo $row['color_depth'] !== null ? (int)$row['color_depth'] : '-'; ?></td>
                    <td><?php echo $row['cookies_enabled'] !== null ? ($row['cookies_enabled'] ? 'Tak' : 'Nie') : '-'; ?></td>
                    <td><?php echo $row['java_enabled'] !== null ? ($row['java_enabled'] ? 'Tak' : 'Nie') : '-'; ?></td>
                    <td><?php echo htmlspecialchars($row['language'] ?? '-'); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
