<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */ ?>
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>125340</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
	<script src="twoj_js.js"></script> 
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>	
	<main> 
		<section class="sekcja1">	
			<div class="container-fluid">
				<?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
					<div class="alert alert-success">Zalogowany jako: <?php echo htmlspecialchars($_SESSION["username"] ?? ''); ?></div>
				<?php else: ?>
					<div class="alert alert-warning">Zaloguj się, aby uzyskać dostęp do wszystkich funkcji.</div>
				<?php endif; ?>
			</div>	
		</section>
	</main>	
	<?php require_once 'footer.php'; ?>	
</body>
</html>
