<?php declare(strict_types=1); ?>
<?php
session_start();

// Block non-PL IP
function is_poland() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $context = @stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents("http://ipinfo.io/{$ip}/geo", false, $context);
    if ($json === false) return true; // allow if service unavailable
    $d = json_decode($json);
    if (!$d || empty($d->country)) return true;
    return strtoupper($d->country) === 'PL';
}

if (!is_poland()) {
    echo "Logowanie zablokowane: dostęp tylko z terytorium Polski.<br><a href='index3.php'>Powrót</a>";
    exit();
}

$user = htmlentities($_POST['user'] ?? '', ENT_QUOTES, "UTF-8");
$pass = htmlentities($_POST['pass'] ?? '', ENT_QUOTES, "UTF-8");

$link = mysqli_connect("TU_HOST_BAZY", "TU_UZYTKOWNIK_BAZY", "TU_HASLO_BAZY", "TU_NAZWA_BAZY");
mysqli_query($link, "SET NAMES 'utf8'");
$result = mysqli_query($link, "SELECT * FROM users WHERE username='$user'");
$rekord = mysqli_fetch_array($result);

if ($rekord && $rekord['password'] == $pass) {
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $rekord['username'];
    mysqli_close($link);
    header('Location: index4.php');
    exit();
} else {
    mysqli_close($link);
    echo "Błąd logowania<br><a href='index3.php'>Spróbuj ponownie</a>";
}
?>
