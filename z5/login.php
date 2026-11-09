<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
start_session();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    if (empty($username)) {
        $username_err = "Wprowadź nazwę użytkownika.";
    }
    $password = trim($_POST["password"] ?? '');
    if (empty($password)) {
        $password_err = "Wprowadź hasło.";
    }

    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password, avatar FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username_db, $hashed_password, $avatar);
                    if (mysqli_stmt_fetch($stmt)) {
                        if ($hashed_password === $password || password_verify($password, $hashed_password)) {
                            /* === POPRAWNE LOGOWANIE === */
                            session_regenerate_id();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = (int)$id;
                            $_SESSION["username"] = $username_db;
                            $_SESSION["avatar"] = $avatar;
                            $_SESSION["is_admin"] = ($username_db === 'admin');

                            /* Katalog macierzysty (nazwa = login) */
                            ensure_home((string)$username_db);

                            zapisz_logowanie($conn, (string)$username_db, 'success');

                            /* Ostrzezenie o ostatnim blednym logowaniu (jesli bylo w ciagu 24h) */
                            $now = time();
                            $w = mysqli_prepare($conn,
                                "SELECT datetime, ipaddress FROM goscieportalu
                                 WHERE username = ? AND result = 'failure' AND datetime >= ?
                                 ORDER BY datetime DESC LIMIT 1");
                            if ($w) {
                                $od = date('Y-m-d H:i:s', $now - 86400);
                                mysqli_stmt_bind_param($w, "ss", $username_db, $od);
                                mysqli_stmt_execute($w);
                                mysqli_stmt_bind_result($w, $f_dt, $f_ip);
                                if (mysqli_stmt_fetch($w)) {
                                    $_SESSION['login_warning'] =
                                        "Ostatnie nieudane logowanie: {$f_dt} z adresu IP {$f_ip}.";
                                }
                                mysqli_stmt_close($w);
                            }

                            header("location: index.php");
                            exit;
                        } else {
                            /* === BLEDNE HASLO === */
                            $login_err = "Nieprawidłowa nazwa użytkownika lub hasło.";
                            zapisz_logowanie($conn, (string)$username_db, 'failure');
                        }
                    }
                } else {
                    /* Uzytkownik nie istnieje - rowniez rejestrujemy probe */
                    $login_err = "Nieprawidłowa nazwa użytkownika lub hasło.";
                    zapisz_logowanie($conn, $username, 'failure');
                }
            } else {
                $login_err = "Błąd. Spróbuj ponownie później.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
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
                    <div class="col-md-5">
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="text-center">Logowanie - MyCloud (z5)</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($login_err)) { echo '<div class="alert alert-danger">' . h($login_err) . '</div>'; } ?>
                                <form action="<?php echo h($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nazwa użytkownika</label>
                                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo h($username); ?>" autocomplete="username">
                                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Hasło</label>
                                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" autocomplete="current-password">
                                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                    </div>
                                    <div class="mb-3 d-grid">
                                        <input type="submit" class="btn btn-primary" value="Zaloguj">
                                    </div>
                                    <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
                                </form>
                            </div>
                        </div>
                        <div class="alert alert-secondary mt-3 small">
                            Konto administratora: <b>admin / TU_HASLO</b><br>
                            Konta testowe: <b>user1 / TU_HASLO</b>, <b>user2 / TU_HASLO</b>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
