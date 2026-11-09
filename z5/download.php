<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!valid_username_dir($user)) {
    http_response_code(403);
    exit;
}

/* Plik wlasny albo plik udostepniony przez innego uzytkownika. */
$owner = (string)($_GET['owner'] ?? $user);
if (!valid_username_dir($owner)) {
    http_response_code(403);
    exit;
}
$rel  = norm_rel((string)($_GET['path'] ?? ''));
$real = ($rel === null || $rel === '') ? null : file_abs($owner, $rel);

if ($real === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "404 - plik nie istnieje lub nie masz do niego dostępu.";
    exit;
}

if ($owner !== $user && !share_granted($conn, $owner, (string)$rel, $user)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 - ten plik nie został Ci udostępniony.";
    exit;
}

$inline = (isset($_GET['inline']) && $_GET['inline'] === '1');
$name = basename($real);
$size = (int)@filesize($real);
$mime = mime_for($real, $name);

$fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
$disp = ($inline ? 'inline' : 'attachment')
      . '; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($name);

/* Obsluga zakresow (Range) - umozliwia przewijanie audio/wideo */
$start = 0;
$end = $size > 0 ? $size - 1 : 0;
$partial = false;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', (string)$_SERVER['HTTP_RANGE'], $m)) {
    $range_start = $m[1] === '' ? null : (int)$m[1];
    $range_end   = $m[2] === '' ? null : (int)$m[2];
    if ($range_start === null && $range_end === null) {
        /* ignorujemy */
    } elseif ($range_start === null) {
        $start = max(0, $size - (int)$range_end);
        $end = $size - 1;
        $partial = true;
    } else {
        $start = $range_start;
        $end = $range_end === null ? $size - 1 : min($range_end, $size - 1);
        $partial = true;
    }
    if ($size === 0 || $start > $end || $start >= $size) {
        http_response_code(416);
        header("Content-Range: bytes */$size");
        exit;
    }
}

$length = $size === 0 ? 0 : ($end - $start + 1);

if ($partial) {
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
} else {
    header('HTTP/1.1 200 OK');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . $length);
header('Content-Disposition: ' . $disp);
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'HEAD' || $length === 0) {
    exit;
}

$fp = @fopen($real, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit;
}
fseek($fp, $start);
$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, (int)min(8192, $remaining));
    if ($chunk === false) break;
    echo $chunk;
    $remaining -= strlen($chunk);
}
fclose($fp);
exit;
?>
