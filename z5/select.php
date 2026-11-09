<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) { http_response_code(403); die("Nieprawidłowa nazwa użytkownika."); }

$rel = norm_rel((string)($_GET['dir'] ?? ''));
if ($rel === null) $rel = '';
$abs = dir_abs($user, $rel);
if ($abs === null) {
    $_SESSION['flash_err'] = "Katalog docelowy nie istnieje.";
    header("location: index.php");
    exit;
}
$view = $_SESSION['z5_view'] ?? 'media';
$view_q = 'view=' . rawurlencode($view);
$enc_dir = rawurlencode($rel);
$back = 'index.php' . ($rel !== '' ? '?dir=' . $enc_dir . '&amp;' . $view_q : '?' . $view_q);
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
                                <h4>Wgrywanie pliku (z5)</h4>
                            </div>
                            <div class="card-body">
                                <p>Katalog docelowy: <code>files/<?php echo h($user); ?><?php echo $rel !== '' ? '/' . h($rel) : ''; ?></code></p>
                                <form action="upload.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="dir" value="<?php echo h($rel); ?>">
                                    <div class="mb-3">
                                        <label for="fileToUpload" class="form-label">Wybierz plik</label>
                                        <input type="file" name="fileToUpload" id="fileToUpload" class="form-control" required>
                                        <div class="form-text">
                                            Maksymalny rozmiar pliku: <b><?php echo h(format_bytes(MAX_UPLOAD_BYTES)); ?></b>.
                                            To limit hostingu InfinityFree &mdash; większe pliki są automatycznie
                                            usuwane przez serwer. Wideo najlepiej przechowywać na zewnętrznym
                                            serwisie (np. YouTube) i podlinkować.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <input type="submit" class="btn btn-primary" value="Wgraj">
                                        <a class="btn btn-secondary" href="<?php echo $back; ?>">Anuluj</a>
                                    </div>
                                </form>
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
