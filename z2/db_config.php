<?php
/* Konfiguracja polaczenia z baza danych - hosting 7m.pl */
define('DB_SERVER', 'TU_HOST_BAZY');
define('DB_USERNAME', 'TU_UZYTKOWNIK_BAZY');
define('DB_PASSWORD', 'TU_HASLO_BAZY');
define('DB_NAME', 'TU_NAZWA_BAZY');

/* Polaczenie z baza danych */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

/* Ustawienie polskich znakow (kodowanie UTF-8) */
mysqli_set_charset($conn, 'utf8');
?>
