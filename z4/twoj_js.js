/* ============================================================================
   Biblioteka JS aplikacji z4
   1) myLoadHeader() - dynamiczne wstawienie pliku header.php (navbar) do #myHeader
   2) obsluga dzwiekowego ostrzegania o awariach uslug (MP3, 3 rodzaje, mozliwosc wylaczenia)
   ============================================================================ */

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

/* ---------------------------------------------------------------------------
   Dzwiekowe ostrzeganie o awarii (wymog z punktu 14f instrukcji).
   Domyslnie wlaczone. Wybor zapamietywany w localStorage przegladarki.
   --------------------------------------------------------------------------- */
(function ()
{
	var KEY_ENABLED = 'z4_sound_enabled';   /* '1' lub '0' */
	var KEY_TYPE    = 'z4_sound_type';      /* alert1 | alert2 | alert3 */
	var audio = null;

	function soundEnabled() { return localStorage.getItem(KEY_ENABLED) !== '0'; }
	function soundType()    { return localStorage.getItem(KEY_TYPE) || 'alert1'; }

	function updateUI()
	{
		var icon   = document.getElementById('z4SoundIcon');
		var status = document.getElementById('z4SoundStatus');
		var sel    = document.getElementById('z4SoundType');
		var btn    = document.getElementById('z4SoundToggle');
		if (!icon) return;

		var on = soundEnabled();
		icon.src = 'media/icons/' + (on ? 'sound_on' : 'sound_off') + '.svg';
		if (status) status.innerHTML = 'Ostrzeganie dźwiękowe: <b>' + (on ? 'włączone' : 'wyłączone') + '</b>';
		if (sel) { sel.value = soundType(); sel.disabled = !on; }
		if (btn) btn.textContent = on ? 'Wyłącz' : 'Włącz';
	}

	/* Czy na stronie istnieje host, ktorego awaria trwa > 40 s? */
	function alarmActive()
	{
		return document.querySelector('[data-alert="1"]') !== null;
	}

	function playAlarm()
	{
		if (!soundEnabled() || !alarmActive()) return;
		try {
			if (audio) { audio.pause(); }
			audio = new Audio('media/sounds/' + soundType() + '.mp3');
			audio.loop = true;
			audio.volume = 0.8;
			var p = audio.play();
			if (p && p.catch) {
				/* Autoplay moze byc zablokowany do pierwszego klikniecia uzytkownika.
				   Wtedy ponawiamy odtwarzanie przy pierwszej interakcji. */
				p.catch(function () {});
			}
		} catch (e) { /* ignorujemy */ }
	}

	function stopAlarm()
	{
		if (audio) { audio.pause(); audio.currentTime = 0; }
	}

	document.addEventListener('DOMContentLoaded', function ()
	{
		updateUI();

		var sel = document.getElementById('z4SoundType');
		var btn = document.getElementById('z4SoundToggle');

		if (sel) sel.addEventListener('change', function () {
			localStorage.setItem(KEY_TYPE, this.value);
			playAlarm();
		});

		if (btn) btn.addEventListener('click', function () {
			localStorage.setItem(KEY_ENABLED, soundEnabled() ? '0' : '1');
			updateUI();
			if (soundEnabled()) { playAlarm(); } else { stopAlarm(); }
		});

		playAlarm();

		/* Ponowna proba po pierwszym kliknieciu (obejscie polityki autoplay) */
		document.addEventListener('click', function once () {
			playAlarm();
			document.removeEventListener('click', once);
		});
	});

	window.z4StopAlarm = stopAlarm;
})();
