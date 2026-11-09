<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dodaj1.php');
    exit();
}

$host = trim($_POST['host'] ?? '');
$port = (int)($_POST['port'] ?? 0);
$uid  = (int)($_SESSION['id'] ?? 0);

if ($host === '') {
    header('Location: dodaj1.php?err=' . urlencode('Podaj nazwę hosta lub adres IP.'));
    exit();
}
if ($port < 1 || $port > 65535) {
    header('Location: dodaj1.php?err=' . urlencode('Numer portu musi być w zakresie 1-65535.'));
    exit();
}

/* INSERT INTO domeny - dodanie nowej uslugi do monitorowania.
   user_id pochodzi z numerycznego id zalogowanego uzytkownika (sesja). */
$sql = "INSERT INTO domeny (host, port, user_id) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $uid);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header('Location: dodaj1.php?msg=' . urlencode('Dodano host ' . $host . ':' . $port . ' do monitorowania.'));
        exit();
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
header('Location: dodaj1.php?err=' . urlencode('Nie udało się dodać hosta (błąd bazy danych).'));
exit();
?>
