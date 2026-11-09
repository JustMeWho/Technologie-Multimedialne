<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<HEAD>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>125340</title>
</HEAD>
<BODY>
<?php
  /* UWAGA: TO SKRYPT PODATNY NA SQL injection – nigdy nie używać go poza tym testem !!!! */

  $user=$_POST['user'] ?? '';                        // login z formularza
  $pass=$_POST['pass'] ?? '';                        // hasło z formularza

  $link = mysqli_connect("TU_HOST_BAZY", "TU_UZYTKOWNIK_BAZY", "TU_HASLO_BAZY", "TU_NAZWA_BAZY");
  if(!$link) { echo "Error: ". mysqli_connect_errno()." ".mysqli_connect_error(); }   // obsługa błędu połączenia z BD
  mysqli_query($link, "SET NAMES 'utf8'");           // ustawienie polskich znaków
  $result = mysqli_query($link, "SELECT * FROM users WHERE (username='$user') and (password='$pass')");   // TU JEST PIES POGRZEBANY
  $rekord = mysqli_fetch_array($result);             // pobieranie wiersza z BD, struktura zmiennej jak w BD
  if(!$rekord)                                       // Jeśli brak, to nie ma użytkownika o podanym loginie
  {
      mysqli_close($link);                           // zamknięcie połączenia z BD
      echo "Blad nazwy użytkownika lub hasla";
   }
   else
   {
       echo "Logowanie Ok. User: {$rekord['username']}. Hasło: {$rekord['password']}";   // Jeśli $rekord istnieje
   }
?>
<br><a href="index1.php">Powrót do formularza</a> | <a href="index.php">Powrót do z1</a> | <a href="../index.php">Powrót do listy zadań</a>
</BODY>
</HTML>
