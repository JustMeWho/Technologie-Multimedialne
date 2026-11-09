<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kasuj1.php');
    exit();
}

$host = trim($_POST['host'] ?? '');
$port = (int)($_POST['port'] ?? 0);
$uid  = (int)($_SESSION['id'] ?? 0);
$admin = is_admin();

if ($host === '' || $port < 1 || $port > 65535) {
    header('Location: kasuj1.php?err=' . urlencode('Podaj poprawny host i port.'));
    exit();
}

/* DELETE ... WHERE - usuniecie wybranej uslugi.
   Zwykly uzytkownik moze usunac wylacznie wlasne wpisy (warunek user_id). */
if ($admin) {
    $sql = "DELETE FROM domeny WHERE host = ? AND port = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $host, $port);
    }
} else {
    $sql = "DELETE FROM domeny WHERE host = ? AND port = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $uid);
    }
}

if ($stmt) {
    $ok = mysqli_stmt_execute($stmt);
    $aff = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if ($ok && $aff > 0) {
        header('Location: kasuj1.php?msg=' . urlencode('Usunięto ' . $host . ':' . $port . ' z monitorowania.'));
        exit();
    }
    header('Location: kasuj1.php?err=' . urlencode('Nie znaleziono wskazanej usługi (lub nie masz do niej uprawnień).'));
    exit();
}
mysqli_close($conn);
header('Location: kasuj1.php?err=' . urlencode('Błąd bazy danych podczas usuwania.'));
exit();
?>
