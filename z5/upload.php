<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) { http_response_code(403); die("Nieprawidłowa nazwa użytkownika."); }

$rel = norm_rel((string)($_POST['dir'] ?? ''));
if ($rel === null) $rel = '';
$view = $_SESSION['z5_view'] ?? 'media';

$redirect = 'index.php?' . ($rel !== '' ? 'dir=' . rawurlencode($rel) . '&' : '') . 'view=' . rawurlencode($view);

$abs = dir_abs($user, $rel);
if ($abs === null) {
    $_SESSION['flash_err'] = "Katalog docelowy nie istnieje.";
    header("location: " . $redirect);
    exit;
}

if (empty($_FILES) && empty($_POST)) {
    $_SESSION['flash_err'] = "Przesłany plik jest zbyt duży (przekroczono limit hostingu).";
    header("location: " . $redirect);
    exit;
}

if (!isset($_FILES['fileToUpload'])) {
    $_SESSION['flash_err'] = "Nie wybrano pliku.";
    header("location: " . $redirect);
    exit;
}

$f = $_FILES['fileToUpload'];
if ($f['error'] !== UPLOAD_ERR_OK) {
    $komunikaty = [
        UPLOAD_ERR_INI_SIZE   => "Plik przekracza limit upload_max_filesize serwera.",
        UPLOAD_ERR_FORM_SIZE  => "Plik przekracza limit formularza.",
        UPLOAD_ERR_PARTIAL    => "Plik został wgrany tylko częściowo.",
        UPLOAD_ERR_NO_FILE    => "Nie wybrano pliku.",
        UPLOAD_ERR_NO_TMP_DIR => "Brak katalogu tymczasowego na serwerze.",
        UPLOAD_ERR_CANT_WRITE => "Nie można zapisać pliku na serwerze.",
        UPLOAD_ERR_EXTENSION  => "Wgrywanie zatrzymane przez rozszerzenie PHP.",
    ];
    $_SESSION['flash_err'] = $komunikaty[$f['error']] ?? "Nieznany błąd wgrywania (kod {$f['error']}).";
    header("location: " . $redirect);
    exit;
}

if (!is_uploaded_file($f['tmp_name'])) {
    $_SESSION['flash_err'] = "Nieprawidłowe źródło pliku.";
    header("location: " . $redirect);
    exit;
}

/* InfinityFree usuwa pliki wieksze niz 10 MB tuz po wgraniu (limit systemu
   plikow). Sprawdzamy to zanim plik trafi do katalogu uzytkownika, aby zamiast
   tajemniczego znikniecia pliku pokazac czytelny komunikat. */
if ((int)$f['size'] > MAX_UPLOAD_BYTES) {
    $_SESSION['flash_err'] = "Plik jest za duży (" . format_bytes((int)$f['size'])
        . "). Hosting InfinityFree automatycznie usuwa pliki większe niż "
        . format_bytes(MAX_UPLOAD_BYTES) . ". Skompresuj plik lub umieść wideo "
        . "na zewnętrznym serwisie (np. YouTube) i podlinkuj je na stronie.";
    header("location: " . $redirect);
    exit;
}

$orig = basename(str_replace('\\', '/', (string)$f['name']));
if ($orig === '' || $orig[0] === '.') {
    $_SESSION['flash_err'] = "Nazwa pliku nie może być pusta ani zaczynać się od kropki.";
    header("location: " . $redirect);
    exit;
}

$name = unique_filename($abs, $orig);
$target = $abs . '/' . $name;

if (move_uploaded_file($f['tmp_name'], $target)) {
    $_SESSION['flash_msg'] = "Plik został wgrany: " . $name . " (" . format_bytes((int)@filesize($target)) . ").";
} else {
    $_SESSION['flash_err'] = "Nie udało się zapisać pliku w katalogu docelowym.";
}

header("location: " . $redirect);
exit;
?>
