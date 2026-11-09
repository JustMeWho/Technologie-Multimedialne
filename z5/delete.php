<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) { http_response_code(403); die("Nieprawidłowa nazwa użytkownika."); }

$rel = norm_rel((string)($_GET['path'] ?? $_POST['path'] ?? ''));
$cur = norm_rel((string)($_GET['dir'] ?? $_POST['dir'] ?? ''));
if ($cur === null) $cur = '';
$view = $_SESSION['z5_view'] ?? 'media';
$redirect = 'index.php?' . ($cur !== '' ? 'dir=' . rawurlencode($cur) . '&' : '') . 'view=' . rawurlencode($view);

if ($rel === null || $rel === '') {
    $_SESSION['flash_err'] = "Nieprawidłowa nazwa elementu do usunięcia.";
    header("location: " . $redirect);
    exit;
}

$home = home_abs($user);
$abs_raw = $home . '/' . $rel;
$real = realpath($abs_raw);
$rhome = realpath($home);

if ($real === false || $rhome === false || strpos($real, $rhome . DIRECTORY_SEPARATOR) !== 0) {
    $_SESSION['flash_err'] = "Nie znaleziono elementu lub ścieżka jest niedozwolona.";
    header("location: " . $redirect);
    exit;
}

$name = basename($real);
if (is_dir($real) && !is_link($real)) {
    if (rrmdir($real)) {
        $_SESSION['flash_msg'] = "Usunięto katalog (wraz z zawartością): " . $name . ".";
    } else {
        $_SESSION['flash_err'] = "Nie udało się usunąć katalogu: " . $name . ".";
    }
} elseif (is_file($real)) {
    if (@unlink($real)) {
        $_SESSION['flash_msg'] = "Usunięto plik: " . $name . ".";
    } else {
        $_SESSION['flash_err'] = "Nie udało się usunąć pliku: " . $name . ".";
    }
} else {
    $_SESSION['flash_err'] = "Wskazany element nie jest plikiem ani katalogiem.";
}

header("location: " . $redirect);
exit;
?>
