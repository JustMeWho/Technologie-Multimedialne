<?php
/* Konfiguracja polaczenia z baza danych - hosting InfinityFree (125340.kesug.com) */
define('DB_SERVER', 'TU_HOST_BAZY');
define('DB_USERNAME', 'TU_UZYTKOWNIK_BAZY');
define('DB_PASSWORD', 'TU_HASLO_BAZY');
define('DB_NAME', 'TU_NAZWA_BAZY');

/* PHP 8.1+ domyslnie zglasza bledy mysqli jako wyjatki. Wylaczamy to, aby skrypty
   zachowywaly sie zgodnie z instrukcja laboratoryjna (zwracaly false i pozwalaly
   obsluzyc blad samodzielnie). */
mysqli_report(MYSQLI_REPORT_OFF);

/* Polaczenie z baza danych */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

/* Ustawienie polskich znakow (kodowanie UTF-8) */
mysqli_set_charset($conn, 'utf8mb4');

/* Strefa czasowa aplikacji - spojna dla zapisow PHP i wyswietlania.
   Serwer MySQL hostingu pracuje w innej strefie (US), dlatego znaczniki czasu
   monitoringu zapisujemy i odczytujemy po stronie PHP. */
date_default_timezone_set('Europe/Warsaw');

/* ============================================================================
   Funkcje pomocnicze wspolne dla calej aplikacji z4
   ============================================================================ */

/* Bezpieczne wypisanie tekstu w HTML */
function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* Uruchomienie sesji (tylko raz) */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/* Wymuszenie zalogowania - przekierowanie na strone logowania */
function require_login(): void {
    start_session();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit();
    }
}

/* Czy zalogowany uzytkownik jest administratorem? */
function is_admin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/* Ustalenie typu uslugi na podstawie numeru portu (dobor ikony SVG) */
function service_type(int $port): string {
    if (in_array($port, [80, 443, 8000, 8080, 8443], true)) return 'web';
    if (in_array($port, [1433, 3306, 5432, 27017], true))    return 'db';
    if (in_array($port, [25, 110, 143, 465, 587, 993, 995], true)) return 'mail';
    return 'generic';
}

/* Sciezka do ikony SVG dla danego portu i stanu uslugi */
function service_icon(int $port, bool $online): string {
    return 'media/icons/' . service_type($port) . '_' . ($online ? 'online' : 'offline') . '.svg';
}

/* Czytelna nazwa typu uslugi */
function service_name(int $port): string {
    switch (service_type($port)) {
        case 'web':  return 'serwer WWW';
        case 'db':   return 'baza danych';
        case 'mail': return 'serwer poczty';
        default:     return 'inna usluga';
    }
}

/* Format czasu trwania HH:MM:SS */
function format_hms(int $seconds): string {
    $seconds = max(0, $seconds);
    return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
}

/* Pobranie IP goscia portalu (hosting dziala za proxy) */
function ip_goscia(): string {
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return trim($_SERVER['HTTP_X_REAL_IP']);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* Rozpoznanie przegladarki goscia na podstawie naglowka User-Agent */
function przegladarka_goscia(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'nieznana';
    if (preg_match('/Edg\/([0-9.]+)/', $ua, $m))     return 'Edge ' . $m[1];
    if (preg_match('/OPR\/([0-9.]+)/', $ua, $m))     return 'Opera ' . $m[1];
    if (preg_match('/Chrome\/([0-9.]+)/', $ua, $m))  return 'Chrome ' . $m[1];
    if (preg_match('/Firefox\/([0-9.]+)/', $ua, $m)) return 'Firefox ' . $m[1];
    if (preg_match('/Version\/([0-9.]+).*Safari/', $ua, $m)) return 'Safari ' . $m[1];
    return 'Inna (' . mb_substr($ua, 0, 60) . ')';
}

/* Zapis wizyty goscia portalu (tabela goscieportalu) - czesc templatki z3 */
function zapisz_wizyte(mysqli $conn): void {
    $ip = ip_goscia();
    $browser = przegladarka_goscia();
    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    if ($lang !== null) {
        $lang = substr(explode(',', $lang)[0], 0, 50);
    }
    $stmt = mysqli_prepare($conn,
        "INSERT INTO goscieportalu (ipaddress, datetime, browser, language) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $dt = date('Y-m-d H:i:s');
        mysqli_stmt_bind_param($stmt, 'ssss', $ip, $dt, $browser, $lang);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
