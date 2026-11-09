<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */ ?>
<?php
session_start();
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
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 1.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie1_1.php"> <img src="media/menu_icons/info.svg" height="18"> Polecenie 1.1 </a></li>
						<li><a class="dropdown-item" href="polecenie1_2.php"> <img src="media/menu_icons/help.svg" height="18"> Polecenie 1.2 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 2.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie2_1.php"> Polecenie 2.1 </a></li>
						<li><a class="dropdown-item" href="polecenie2_2.php"> Polecenie 2.2 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 3.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie3_1.php"> Polecenie 3.1 </a></li>
						<li><a class="dropdown-item" href="polecenie3_2.php"> Polecenie 3.2 </a></li>
						<li><a class="dropdown-item" href="polecenie3_3.php"> Polecenie 3.3 </a></li>
					</ul>
				</li>
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
