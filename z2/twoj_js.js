/* Jedyna funkcja biblioteki JS - wstawia zawartość pliku header.php (navbar) do DIVa myHeader.
   Dzięki temu nie trzeba wstawiać navbaru do wielu plików aplikacji.

   UWAGA (specyfika hostingu 7m.pl): hosting wstrzykuje na koniec strony własny skrypt
   (robot.js), który nadpisuje zdarzenie window.onload - przez co wywołanie
   <body onload="myLoadHeader()"> nie dociera do tej funkcji i navbar się nie ładował.
   Dlatego funkcja wywoływana jest również bezpośrednio po jej zdefiniowaniu,
   a flaga myHeaderLoaded zabezpiecza przed podwójnym wstawieniem navbaru. */

var myHeaderLoaded = false;

function myLoadHeader()
{
    if (myHeaderLoaded) return;
    myHeaderLoaded = true;

    $(document).ready(function()
    {
        $('#myHeader').load("header.php");
    });
}

myLoadHeader();
