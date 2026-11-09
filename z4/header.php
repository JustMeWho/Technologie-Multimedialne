<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once __DIR__ . '/db_config.php';
start_session();
?>
<header>
<div class="container">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mt-0 mb-0 ms-0 me-0 pt-0 pb-0 ps-0 pe-0">
	<div class="container-fluid">
		<div class="collapse navbar-collapse" id="main_nav">
			<ul class="navbar-nav">
				<li class="nav-item">
					<a class="nav-link" href="../index.php">Strona główna</a>
				</li>
				<?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
				<li class="nav-item">
					<a class="nav-link" href="index.php">Panel z4</a>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Monitorowanie</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="index1.php"> <img src="media/menu_icons/info.svg" height="18"> Lista monitorowanych usług </a></li>
						<li><a class="dropdown-item" href="dodaj1.php"> Dodaj host / port </a></li>
						<li><a class="dropdown-item" href="kasuj1.php"> Usuń host / port </a></li>
					</ul>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="goscie.php">Goście portalu</a>
				</li>
				<?php if (is_admin()): ?>
				<li class="nav-item">
					<a class="nav-link" href="staff.php">Pracownicy</a>
				</li>
				<li class="nav-item">
					<span class="navbar-text text-warning ms-2">[admin: <?php echo h($_SESSION["username"] ?? ''); ?>]</span>
				</li>
				<?php endif; ?>
				<li class="nav-item">
					<a class="nav-link" href="logout.php">Wylogowanie <img src="media/menu_icons/sign-out.svg" height="18"></a>
				</li>
				<?php else: ?>
				<li class="nav-item">
					<a class="nav-link" href="login.php">Logowanie</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="register.php">Rejestracja</a>
				</li>
				<?php endif; ?>
			</ul>
		</div>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav"  aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
	</div>
</nav>
</div>
</header>
