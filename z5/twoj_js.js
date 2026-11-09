/* ============================================================================
   Biblioteka JS aplikacji z5 (MyCloud)
   myLoadHeader() - dynamiczne wstawienie pliku header.php (navbar) do #myHeader
   ============================================================================ */

var myHeaderLoaded = false;

function myLoadHeader()
{
	if (myHeaderLoaded) return;
	myHeaderLoaded = true;

	$(document).ready(function ()
	{
		$('#myHeader').load("header.php");
	});
}

myLoadHeader();

/* Potwierdzenie usuniecia pliku/katalogu oraz cofniecia udostepnienia */
document.addEventListener('DOMContentLoaded', function ()
{
	document.querySelectorAll('a.js-confirm').forEach(function (a)
	{
		a.addEventListener('click', function (e)
		{
			var msg = a.getAttribute('data-confirm') || 'Czy na pewno usunąć?';
			if (!window.confirm(msg)) e.preventDefault();
		});
	});

	document.querySelectorAll('form.js-confirm').forEach(function (f)
	{
		f.addEventListener('submit', function (e)
		{
			var msg = f.getAttribute('data-confirm') || 'Czy na pewno wykonać tę operację?';
			if (!window.confirm(msg)) e.preventDefault();
		});
	});
});
