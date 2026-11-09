<?php
/* Konfiguracja polaczenia z baza danych - hosting InfinityFree (125340.kesug.com)
   Wspolna baza dla zadan z1-z5. */
define('DB_SERVER', 'TU_HOST_BAZY');
define('DB_USERNAME', 'TU_UZYTKOWNIK_BAZY');
define('DB_PASSWORD', 'TU_HASLO_BAZY');
define('DB_NAME', 'TU_NAZWA_BAZY');

/* Limit rozmiaru pojedynczego pliku narzucony przez hosting InfinityFree.
   Pliki wieksze niz 10 MB sa automatycznie usuwane przez serwer tuz po
   wgraniu (limit na poziomie systemu plikow, niezalezny od PHP). */
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);

/* PHP 8.1+ domyslnie zglasza bledy mysqli jako wyjatki. Wylaczamy to, aby skrypty
   zachowywaly sie zgodnie z instrukcja laboratoryjna (zwracaly false). */
mysqli_report(MYSQLI_REPORT_OFF);

/* Polaczenie z baza danych */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

/* Strefa czasowa aplikacji - spojna dla zapisow PHP i wyswietlania.
   Serwer MySQL hostingu pracuje w innej strefie (US), dlatego znaczniki czasu
   zapisujemy i odczytujemy po stronie PHP. */
date_default_timezone_set('Europe/Warsaw');

/* ============================================================================
   Funkcje pomocnicze wspolne dla calej aplikacji z5 (MyCloud)
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

/* Zapis proby logowania (pomyslnej lub blednej) do tabeli goscieportalu.
   $result: 'success' albo 'failure'. */
function zapisz_logowanie(mysqli $conn, string $username, string $result): void {
    $ip = ip_goscia();
    $browser = przegladarka_goscia();
    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    if ($lang !== null) {
        $lang = substr(explode(',', $lang)[0], 0, 50);
    }
    $dt = date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($conn,
        "INSERT INTO goscieportalu (ipaddress, datetime, browser, language, username, result)
         VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssssss', $ip, $dt, $browser, $lang, $username, $result);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/* ---------------------------------------------------------------------------
   Obsluga katalogu macierzystego uzytkownika (dysk sieciowy)
   --------------------------------------------------------------------------- */

/* Dozwolone nazwy uzytkownikow / katalogow: litery, cyfry, _ i - */
function valid_username_dir(string $u): bool {
    return (bool)preg_match('/^[A-Za-z0-9_-]{3,32}$/', $u);
}

/* Sciezka absolutna katalogu macierzystego uzytkownika */
function home_abs(string $username): string {
    return __DIR__ . '/files/' . $username;
}

/* Upewnienie sie, ze katalog macierzysty istnieje (tworzy go w razie potrzeby) */
function ensure_home(string $username): bool {
    if (!valid_username_dir($username)) return false;
    $d = home_abs($username);
    if (!is_dir($d)) {
        @mkdir($d, 0775, true);
    }
    return is_dir($d);
}

/* Pojedynczy, bezpieczny segment sciezki (nazwa pliku lub katalogu) */
function valid_segment(string $s): bool {
    if ($s === '' || $s === '.' || $s === '..') return false;
    if (preg_match('/[\/\\\\\x00]/', $s)) return false;
    return mb_strlen($s) <= 120;
}

/* Normalizacja sciezki wzglednej (wzgledem katalogu macierzystego).
   Zwraca znormalizowana sciezke lub null przy probie wyjscia poza katalog. */
function norm_rel(string $rel): ?string {
    $rel = str_replace('\\', '/', trim($rel));
    $rel = trim($rel, '/');
    if ($rel === '') return '';
    $parts = explode('/', $rel);
    foreach ($parts as $p) {
        if (!valid_segment($p)) return null;
    }
    return implode('/', $parts);
}

/* Liczba poziomow zagniezdzenia (0 = katalog macierzysty) */
function rel_depth(string $rel): int {
    $rel = norm_rel($rel);
    if ($rel === null || $rel === '') return 0;
    return count(explode('/', $rel));
}

/* Bezpieczne rozwiazanie katalogu (musi istniec i lezec w katalogu macierzystym) */
function dir_abs(string $username, string $rel): ?string {
    $rel = norm_rel($rel);
    if ($rel === null) return null;
    $home = home_abs($username);
    $abs  = $home . ($rel === '' ? '' : '/' . $rel);
    $real = realpath($abs);
    $rhome = realpath($home);
    if ($real === false || $rhome === false) return null;
    if (!is_dir($real)) return null;
    if ($real !== $rhome && strpos($real, $rhome . DIRECTORY_SEPARATOR) !== 0) return null;
    return $real;
}

/* Bezpieczne rozwiazanie pliku (musi istniec i lezec w katalogu macierzystym) */
function file_abs(string $username, string $rel): ?string {
    $rel = norm_rel($rel);
    if ($rel === null || $rel === '') return null;
    $home = home_abs($username);
    $abs  = $home . '/' . $rel;
    $real = realpath($abs);
    $rhome = realpath($home);
    if ($real === false || $rhome === false) return null;
    if (!is_file($real)) return null;
    if (strpos($real, $rhome . DIRECTORY_SEPARATOR) !== 0) return null;
    return $real;
}

/* Rozpoznanie rodzaju pliku na podstawie rozszerzenia */
function file_kind(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $img = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];
    $vid = ['mp4', 'webm', 'mov', 'm4v', 'ogv'];
    $aud = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
    if (in_array($ext, $img, true)) return 'image';
    if (in_array($ext, $vid, true)) return 'video';
    if (in_array($ext, $aud, true)) return 'audio';
    return 'other';
}

/* Typ MIME pliku (do strumieniowania w download.php) */
function mime_for(string $path, string $name): string {
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($path);
        if ($m) return $m;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4', 'aac' => 'audio/aac', 'flac' => 'audio/flac',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
        'ogv' => 'video/ogg', 'pdf' => 'application/pdf', 'txt' => 'text/plain',
        'zip' => 'application/zip',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}

/* Czytelny rozmiar pliku */
function format_bytes($bytes): string {
    $bytes = (int)$bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $x = (float)$bytes;
    while ($x >= 1024 && $i < count($units) - 1) { $x /= 1024; $i++; }
    return ($i === 0 ? (string)$bytes : number_format($x, 1, ',', ' ')) . ' ' . $units[$i];
}

/* Laczne zuzycie miejsca w katalogu (rekurencyjnie) */
function dir_size(string $dir): int {
    $sum = 0;
    $it = @scandir($dir);
    if (!$it) return 0;
    foreach ($it as $e) {
        if ($e === '.' || $e === '..') continue;
        $p = $dir . '/' . $e;
        if (is_dir($p)) $sum += dir_size($p);
        elseif (is_file($p)) $sum += (int)@filesize($p);
    }
    return $sum;
}

/* Rekurencyjne usuniecie katalogu (najpierw pliki, potem katalog) */
function rrmdir(string $dir): bool {
    $it = @scandir($dir);
    if (!$it) return false;
    foreach ($it as $e) {
        if ($e === '.' || $e === '..') continue;
        $p = $dir . '/' . $e;
        if (is_dir($p) && !is_link($p)) {
            if (!rrmdir($p)) return false;
        } else {
            if (!@unlink($p)) return false;
        }
    }
    return @rmdir($dir);
}

/* Bezpieczna, unikalna nazwa pliku w katalogu docelowym */
function unique_filename(string $dir, string $name): string {
    $name = basename(str_replace('\\', '/', $name));
    $name = str_replace("\0", '', $name);
    if ($name === '' || $name === '.' || $name === '..') {
        $name = 'plik';
    }
    $target = $dir . '/' . $name;
    if (!file_exists($target)) return $name;
    $base = pathinfo($name, PATHINFO_FILENAME);
    $ext  = pathinfo($name, PATHINFO_EXTENSION);
    $ext  = $ext !== '' ? '.' . $ext : '';
    $i = 1;
    do {
        $candidate = $base . '_' . $i . $ext;
        $target = $dir . '/' . $candidate;
        $i++;
    } while (file_exists($target) && $i < 10000);
    return $candidate;
}

/* ============================================================================
   Udostepnianie plikow i katalogow innym uzytkownikom aplikacji
   ============================================================================ */

/* Czy konto o podanej nazwie istnieje? */
function user_exists(mysqli $conn, string $username): bool {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $ok = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ok;
}

/* Lista pozostalych kont (mozliwych odbiorcow udostepnienia) */
function other_users(mysqli $conn, string $exclude): array {
    $out = [];
    $stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE username <> ? ORDER BY username");
    if (!$stmt) return $out;
    mysqli_stmt_bind_param($stmt, 's', $exclude);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $login);
    while (mysqli_stmt_fetch($stmt)) $out[] = (string)$login;
    mysqli_stmt_close($stmt);
    return $out;
}

/* Czy $viewer moze korzystac ze sciezki $path nalezacej do $owner?
   Wystarczy udostepnienie dokladnie tego elementu albo katalogu nadrzednego. */
function share_granted(mysqli $conn, string $owner, string $path, string $viewer): bool {
    if ($owner === $viewer) return true;
    $path = norm_rel($path);
    if ($path === null || $path === '') return false;
    $stmt = mysqli_prepare($conn, "SELECT path FROM udostepnienia WHERE owner = ? AND target = ?");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'ss', $owner, $viewer);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $shared);
    $ok = false;
    while (mysqli_stmt_fetch($stmt)) {
        $sp = (string)$shared;
        if ($path === $sp || strpos($path, $sp . '/') === 0) { $ok = true; break; }
    }
    mysqli_stmt_close($stmt);
    return $ok;
}

/* Udostepnienie o podanym ID, ktorego odbiorca jest $viewer (albo null). */
function get_share_for_target(mysqli $conn, int $id, string $viewer): ?array {
    $stmt = mysqli_prepare($conn,
        "SELECT id, owner, path, target, created_at
         FROM udostepnienia WHERE id = ? AND target = ? LIMIT 1");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'is', $id, $viewer);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sid, $sowner, $spath, $starget, $screated);
    $row = null;
    if (mysqli_stmt_fetch($stmt)) {
        $row = [
            'id'         => (int)$sid,
            'owner'      => (string)$sowner,
            'path'       => (string)$spath,
            'target'     => (string)$starget,
            'created_at' => (string)$screated,
        ];
    }
    mysqli_stmt_close($stmt);
    return $row;
}
?>
