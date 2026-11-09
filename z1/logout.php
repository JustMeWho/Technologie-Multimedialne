<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */ ?>
<?php
session_start();        // uruchomienie sesji
session_unset();        // wyczyszczenie wszystkich zmiennych sesyjnych
session_destroy();      // usunięcie całej sesji
header('Location: index3.php');
exit;
?>
