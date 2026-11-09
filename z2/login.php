<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

function is_poland() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $context = @stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents("http://ipinfo.io/{$ip}/geo", false, $context);
    if ($json === false) return true;
    $d = json_decode($json);
    if (!$d || empty($d->country)) return true;
    return strtoupper($d->country) === 'PL';
}

require_once "db_config.php";
$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!is_poland()) {
        $login_err = "Logowanie zablokowane: dostęp tylko z terytorium Polski.";
    } else {
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
                mysqli_stmt_bind_param($stmt, "s", $param_username);
                $param_username = $username;
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $username_db, $hashed_password, $avatar);
                        if (mysqli_stmt_fetch($stmt)) {
                            if ($hashed_password === $password || password_verify($password, $hashed_password)) {
                                session_regenerate_id();
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username_db;
                                $_SESSION["avatar"] = $avatar;
                                header("location: index.php");
                                exit;
                            } else {
                                $login_err = "Nieprawidłowa nazwa użytkownika lub hasło.";
                            }
                        }
                    } else {
                        $login_err = "Nieprawidłowa nazwa użytkownika lub hasło.";
                    }
                } else {
                    $login_err = "Błąd. Spróbuj ponownie później.";
                }
                mysqli_stmt_close($stmt);
            }
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
    <title>Logowanie</title>
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
                    <div class="col-md-4">
                        <div class="card mt-5">
                            <div class="card-header">
                                <h4 class="text-center">Logowanie</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($login_err)) { echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>'; } ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nazwa użytkownika</label>
                                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Hasło</label>
                                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                    </div>
                                    <div class="mb-3 d-grid">
                                        <input type="submit" class="btn btn-primary" value="Zaloguj">
                                    </div>
                                    <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
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
