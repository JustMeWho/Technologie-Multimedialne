<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
start_session();

$username = $password = $confirm_password = $avatar_path = "";
$username_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    /* Walidacja nazwy uzytkownika (bezpieczna takze jako nazwa katalogu) */
    $username = trim($_POST["username"] ?? '');
    if (empty($username)) {
        $username_err = "Wprowadź nazwę użytkownika.";
    } elseif (!valid_username_dir($username)) {
        $username_err = "Nazwa może zawierać wyłącznie litery, cyfry, znak _ i - (3-32 znaki).";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "Ta nazwa użytkownika jest już zajęta.";
                }
            } else {
                $username_err = "Błąd. Spróbuj ponownie później.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    /* Walidacja hasla */
    $password = trim($_POST["password"] ?? '');
    if (empty($password)) {
        $password_err = "Wprowadź hasło.";
    }

    /* Potwierdzenie hasla */
    $confirm_password = trim($_POST["confirm_password"] ?? '');
    if (empty($confirm_password)) {
        $confirm_password_err = "Potwierdź hasło.";
    } else {
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Hasła nie są identyczne.";
        }
    }

    /* Avatar rejestrujacego sie uzytkownika (opcjonalny) */
    $avatar_path = "media/default.png";
    if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == 0) {
        $allowed = array("jpg" => "image/jpeg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["avatar"]["name"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (array_key_exists($ext, $allowed) && $_FILES["avatar"]["size"] <= 5 * 1024 * 1024) {
            $newname = uniqid() . "." . $ext;
            if (!is_dir("media/avatars")) {
                @mkdir("media/avatars", 0775, true);
            }
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], "media/avatars/" . $newname)) {
                $avatar_path = "media/avatars/" . $newname;
            }
        }
    }

    /* Zapis do bazy danych - haslo szyfrowane funkcja password_hash() */
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_avatar   = $avatar_path;
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_avatar);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                /* Utworzenie katalogu macierzystego o nazwie loginu */
                ensure_home($username);
                mysqli_close($conn);
                header("location: login.php");
                exit;
            } else {
                $username_err = "Coś poszło nie tak. Spróbuj ponownie później.";
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
                    <div class="col-md-6">
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4>Rejestracja - MyCloud (z5)</h4>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo h($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nazwa użytkownika</label>
                                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo h($username); ?>">
                                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                        <div class="form-text">Tylko litery, cyfry, _ i - (3-32 znaki). Nazwa stanie się nazwą Twojego katalogu.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Hasło</label>
                                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Potwierdź hasło</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="avatar" class="form-label">Avatar (opcjonalny)</label>
                                        <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*">
                                    </div>
                                    <div class="mb-3">
                                        <input type="submit" class="btn btn-primary" value="Zarejestruj">
                                        <a class="btn btn-secondary" href="login.php">Anuluj</a>
                                    </div>
                                    <p class="mb-0">Masz już konto? <a href="login.php">Zaloguj się</a></p>
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
