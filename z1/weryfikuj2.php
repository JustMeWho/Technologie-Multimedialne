<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<HEAD>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>125340</title>
</HEAD>
<BODY>
<?php
  /* poprawiona wersja z częściowym wyeliminowaniem podatności na atak typu SQL injection.
     Weryfikacja realizowana w dwóch fazach:
     faza1: wyszukanie w tabeli users takiego samego loginu, jak ten odebrany z formularza,
     faza2: porównanie hasła odebranego z formularza, z hasłem przypisanym do loginu
            zidentyfikowanego w fazie1 w tabeli users. */

  $user=$_POST['user'] ?? '';                        // login z formularza
  $user = htmlentities($user, ENT_QUOTES, "UTF-8");  // rozbrojenie potencjalnej bomby w zmiennej $user
  $pass=$_POST['pass'] ?? '';                        // hasło z formularza
  $pass = htmlentities($pass, ENT_QUOTES, "UTF-8");  // rozbrojenie potencjalnej bomby w zmiennej $pass

  $link = mysqli_connect("TU_HOST_BAZY", "TU_UZYTKOWNIK_BAZY", "TU_HASLO_BAZY", "TU_NAZWA_BAZY");   // połączenie z BD – wpisać swoje dane
  if(!$link) { echo "Błąd: ". mysqli_connect_errno()." ".mysqli_connect_error(); }   // obsługa błędu połączenia z BD
  mysqli_query($link, "SET NAMES 'utf8'");           // ustawienie polskich znaków
  $result = mysqli_query($link, "SELECT * FROM users WHERE username='$user'");   // wiersz, w którym login=login z formularza
  $rekord = mysqli_fetch_array($result);             // wiersz z BD, struktura zmiennej jak w BD
  if(!$rekord)                                       // Jeśli brak, to nie ma użytkownika o podanym loginie
  {
     mysqli_close($link);                            // zamknięcie połączenia z BD
     echo "Brak użytkownika o takim loginie !";      // UWAGA nie wyświetlamy takich podpowiedzi dla hakerów
  }
  else
  {                                                  // jeśli $rekord istnieje
     if($rekord['password']==$pass)                  // czy hasło zgadza się z BD
     {
        echo "Logowanie Ok. User: {$rekord['username']}. Hasło: {$rekord['password']}";
     }
     else
     {
        mysqli_close($link);
        echo "Błąd w haśle !";                       // UWAGA nie wyświetlamy takich podpowiedzi dla hakerów
     }
   }
?>
<br><a href="index2.php">Powrót do formularza</a> | <a href="index.php">Powrót do z1</a> | <a href="../index.php">Powrót do listy zadań</a>
</BODY>
</HTML>
