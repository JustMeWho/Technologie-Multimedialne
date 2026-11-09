<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once __DIR__ . '/db_config.php';
start_session();
$z5_user = $_SESSION['username'] ?? '';
$z5_usage = '';
if (($_SESSION['loggedin'] ?? false) === true && $z5_user !== '' && is_dir(home_abs($z5_user))) {
    $z5_usage = format_bytes(dir_size(home_abs($z5_user)));
}
?>
	<footer class="page-footer mt-auto bg-light border-primary">
		<div class="container-fluid d-flex flex-wrap justify-content-between align-items-center py-1">
			<span>125340 &middot; Zadanie 5 (z5) &middot; MyCloud - dysk sieciowy</span>
			<span>
				<?php if ($z5_user !== ''): ?>
					Zalogowany: <b><?php echo h($z5_user); ?></b>
					<?php if ($z5_usage !== ''): ?>&middot; zajęte miejsce: <b><?php echo h($z5_usage); ?></b><?php endif; ?>
				<?php else: ?>
					MyCloud &middot; hosting InfinityFree
				<?php endif; ?>
			</span>
		</div>
	</footer>
