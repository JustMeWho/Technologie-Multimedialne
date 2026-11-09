<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
	<footer class="page-footer mt-auto bg-light border-primary">
		<div class="container-fluid d-flex flex-wrap justify-content-between align-items-center py-1">
			<span>125340 &middot; Zadanie 4 (z4) &middot; Monitorowanie usług w sieciach IP</span>
			<span class="d-flex align-items-center gap-2">
				<span id="z4SoundStatus">Ostrzeganie dźwiękowe: <b>włączone</b></span>
				<img id="z4SoundIcon" src="media/icons/sound_on.svg" height="18" alt="dźwięk">
				<select id="z4SoundType" class="form-select form-select-sm" style="width:auto" title="Rodzaj dźwięku ostrzegawczego">
					<option value="alert1">Dźwięk 1</option>
					<option value="alert2">Dźwięk 2</option>
					<option value="alert3">Dźwięk 3</option>
				</select>
				<button type="button" id="z4SoundToggle" class="btn btn-sm btn-outline-secondary">Wyłącz</button>
			</span>
		</div>
	</footer>
