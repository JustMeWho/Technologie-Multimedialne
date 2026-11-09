<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) { http_response_code(403); die("Nieprawidłowa nazwa użytkownika."); }

$path = norm_rel((string)($_REQUEST['path'] ?? ''));
$cur  = norm_rel((string)($_REQUEST['dir'] ?? ''));
if ($cur === null) $cur = '';
$view = $_SESSION['z5_view'] ?? 'media';
$view_q = 'view=' . rawurlencode($view);

$back = 'index.php?' . ($cur !== '' ? 'dir=' . rawurlencode($cur) . '&' : '') . $view_q;

if ($path === null || $path === '') {
    $_SESSION['flash_err'] = "Nie wybrano pliku ani katalogu do udostępnienia.";
    header("location: " . $back);
    exit;
}

$home  = home_abs($user);
$rhome = realpath($home);
$real  = realpath($home . '/' . $path);
if ($real === false || $rhome === false || strpos($real, $rhome . DIRECTORY_SEPARATOR) !== 0) {
    $_SESSION['flash_err'] = "Nie znaleziono elementu lub ścieżka jest niedozwolona.";
    header("location: " . $back);
    exit;
}
$name   = basename($real);
$is_dir = is_dir($real);

$err = "";
$target_sel = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = trim((string)($_POST['target'] ?? ''));
    $target_sel = $target;

    if ($target === '') {
        $err = "Wybierz użytkownika, któremu chcesz udostępnić element.";
    } elseif (!valid_username_dir($target)) {
        $err = "Nieprawidłowa nazwa użytkownika.";
    } elseif ($target === $user) {
        $err = "Nie możesz udostępnić elementu samemu sobie.";
    } elseif (!user_exists($conn, $target)) {
        $err = "Użytkownik o tej nazwie nie istnieje.";
    } else {
        $exists = false;
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM udostepnienia WHERE owner = ? AND path = ? AND target = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sss', $user, $path, $target);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
        }

        if ($exists) {
            $_SESSION['flash_msg'] = "Ten element jest już udostępniony użytkownikowi " . $target . ".";
            header("location: udostepnianie.php");
            exit;
        }

        $stmt = mysqli_prepare($conn,
            "INSERT INTO udostepnienia (owner, path, target, created_at) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $now = date('Y-m-d H:i:s');
            mysqli_stmt_bind_param($stmt, 'ssss', $user, $path, $target, $now);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $_SESSION['flash_msg'] = ($is_dir ? "Katalog" : "Plik") . " „" . $name
                    . "” został udostępniony użytkownikowi " . $target . ".";
                header("location: udostepnianie.php");
                exit;
            }
            mysqli_stmt_close($stmt);
        }
        $err = "Nie udało się zapisać udostępnienia. Spróbuj ponownie.";
    }
}

$users = other_users($conn, $user);
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
                                <h4>Udostępnianie elementu (z5)</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($err !== ''): ?>
                                    <div class="alert alert-danger"><?php echo h($err); ?></div>
                                <?php endif; ?>

                                <p>
                                    Element: <b><?php echo h($name); ?></b>
                                    (<?php echo $is_dir ? 'katalog' : 'plik'; ?>)<br>
                                    Ścieżka: <code>files/<?php echo h($user); ?>/<?php echo h($path); ?></code>
                                </p>

                                <?php if (count($users) === 0): ?>
                                    <div class="alert alert-warning">
                                        Brak innych kont w aplikacji. Udostępnianie wymaga co najmniej dwóch użytkowników.
                                    </div>
                                <?php else: ?>
                                    <form action="share.php" method="post">
                                        <input type="hidden" name="path" value="<?php echo h($path); ?>">
                                        <input type="hidden" name="dir" value="<?php echo h($cur); ?>">
                                        <div class="mb-3">
                                            <label for="target" class="form-label">Udostępnij użytkownikowi</label>
                                            <select name="target" id="target" class="form-select" required>
                                                <option value="">-- wybierz konto --</option>
                                                <?php foreach ($users as $u): ?>
                                                    <option value="<?php echo h($u); ?>"<?php echo ($target_sel === $u) ? ' selected' : ''; ?>><?php echo h($u); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">
                                                Odbiorca zobaczy element w zakładce „Udostępnianie” i będzie mógł go pobrać<?php echo $is_dir ? ' (wraz z zawartością katalogu)' : ''; ?>.
                                            </div>
                                        </div>
                                        <input type="submit" class="btn btn-success" value="Udostępnij">
                                        <a class="btn btn-secondary" href="<?php echo h($back); ?>">Anuluj</a>
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
