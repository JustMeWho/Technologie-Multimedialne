<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) { http_response_code(403); die("Nieprawidłowa nazwa użytkownika."); }

$rel = norm_rel((string)($_POST['dir'] ?? $_GET['dir'] ?? ''));
if ($rel === null) $rel = '';
$view = $_SESSION['z5_view'] ?? 'media';
$view_q = 'view=' . rawurlencode($view);
$enc_dir = rawurlencode($rel);
$back_url = 'index.php' . ($rel !== '' ? '?dir=' . $enc_dir . '&' . $view_q : '?' . $view_q);

$abs = dir_abs($user, $rel);
if ($abs === null) {
    $_SESSION['flash_err'] = "Katalog nie istnieje.";
    header("location: index.php");
    exit;
}

$name = "";
$err = "";
$depth = rel_depth($rel);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === "") {
        $err = "Wprowadź nazwę katalogu.";
    } elseif (!valid_segment($name) || $name[0] === '.') {
        $err = "Nazwa nie może zaczynać się od kropki ani zawierać znaków / i \\.";
    } elseif (file_exists($abs . '/' . $name)) {
        $err = "Element o tej nazwie już istnieje w tym katalogu.";
    } elseif ($depth >= 4) {
        $err = "Osiągnięto maksymalny poziom zagnieżdżenia (4).";
    } else {
        if (@mkdir($abs . '/' . $name, 0775)) {
            $_SESSION['flash_msg'] = "Utworzono katalog: " . $name . ".";
            header("location: " . $back_url);
            exit;
        }
        $err = "Nie udało się utworzyć katalogu (sprawdź uprawnienia).";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>125340</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="twoj_js.js"></script>
</head>
<body onload="myLoadHeader()">
    <div id='myHeader'></div>
    <main>
        <section class="sekcja1">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4>Nowy katalog (z5)</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($err !== ''): ?>
                                    <div class="alert alert-danger"><?php echo h($err); ?></div>
                                <?php endif; ?>
                                <p>Katalog nadrzędny: <code>files/<?php echo h($user); ?><?php echo $rel !== '' ? '/' . h($rel) : ''; ?></code>
                                   (poziom <?php echo $depth; ?> z 4)</p>
                                <?php if ($depth >= 4): ?>
                                    <div class="alert alert-warning">Nie można utworzyć kolejnego podkatalogu - osiągnięto limit 4 poziomów.</div>
                                <?php else: ?>
                                <form action="mkdir.php" method="post">
                                    <input type="hidden" name="dir" value="<?php echo h($rel); ?>">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nazwa nowego katalogu</label>
                                        <input type="text" name="name" id="name" class="form-control" value="<?php echo h($name); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="submit" class="btn btn-success" value="Utwórz katalog">
                                        <a class="btn btn-secondary" href="<?php echo h($back_url); ?>">Anuluj</a>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
