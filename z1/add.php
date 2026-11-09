<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>125340</title>
</head>
<BODY>
<?php
  /* Rejestracja nowego użytkownika - wstawienie danych do tabeli users:
     INSERT INTO users (username, password) VALUES ('$user', '$pass'); */

  $user=$_POST['user'] ?? '';                        // login z formularza
  $user = htmlentities($user, ENT_QUOTES, "UTF-8");  // rozbrojenie potencjalnej bomby w zmiennej $user
  $pass=$_POST['pass'] ?? '';                        // hasło z formularza
  $pass = htmlentities($pass, ENT_QUOTES, "UTF-8");  // rozbrojenie potencjalnej bomby w zmiennej $pass
  $pass2=$_POST['pass2'] ?? '';                      // powtórne wprowadzenie hasła

  $link = mysqli_connect("TU_HOST_BAZY", "TU_UZYTKOWNIK_BAZY", "TU_HASLO_BAZY", "TU_NAZWA_BAZY");   // połączenie z BD – wpisać swoje dane
  if(!$link) { echo "Błąd: ". mysqli_connect_errno()." ".mysqli_connect_error(); }   // obsługa błędu połączenia z BD
  mysqli_query($link, "SET NAMES 'utf8'");           // ustawienie polskich znaków

  if ($user === '' || $pass === '' || $pass2 === '') {
      echo "Wszystkie pola są wymagane!";
  } else {
      if ($pass !== $pass2) {
          echo "Hasła nie są identyczne!";
      } else {
          $sql = "INSERT INTO users (username, password) VALUES ('$user', '$pass')";
          if (mysqli_query($link, $sql)) {
              echo "Rejestracja zakończona pomyślnie. Możesz się teraz zalogować.";
              echo '<br><a href="index3.php">Przejdź do logowania</a>';
          } else {
              echo "Błąd rejestracji - być może taki login już istnieje.";
          }
      }
  }
  mysqli_close($link);
?>
<br><a href="rejestruj.php">Powrót do formularza rejestracji</a> | <a href="index.php">Powrót do z1</a> | <a href="../index.php">Powrót do listy zadań</a>
</BODY>
</HTML>
