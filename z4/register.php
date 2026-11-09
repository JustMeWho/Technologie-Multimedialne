<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
start_session();

$username = $password = $confirm_password = $avatar_path = "";
$username_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    /* Walidacja nazwy uzytkownika */
    $username = trim($_POST["username"] ?? '');
    if (empty($username)) {
        $username_err = "Wprowadź nazwę użytkownika.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "Ta nazwa użytkownika jest już zajęta.";
                }
            } else {
                echo "Błąd. Spróbuj ponownie później.";
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
        if (array_key_exists($ext, $allowed)) {
            if ($_FILES["avatar"]["size"] <= 5 * 1024 * 1024) {
                $newname = uniqid() . "." . $ext;
                $upload_path = "media/avatars/" . $newname;
                if (!is_dir("media/avatars")) {
                    @mkdir("media/avatars", 0777, true);
                }
                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $upload_path)) {
                    $avatar_path = $upload_path;
                }
            }
        }
    }

    /* Zapis do bazy danych - haslo szyfrowane funkcja password_hash() */
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_avatar);
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_avatar = $avatar_path;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                header("location: login.php");
                exit;
            } else {
                echo "Coś poszło nie tak. Spróbuj ponownie później.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="twoj_css.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script type="text/javascript" src="twoj_js.js"></script>
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
                                <h4>Rejestracja (z4)</h4>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo h($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nazwa użytkownika</label>
                                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo h($username); ?>">
                                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Hasło</label>
                                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Potwierdź hasło</label>
                                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="avatar" class="form-label">Avatar (opcjonalny)</label>
                                        <input type="file" name="avatar" class="form-control" accept="image/*">
                                    </div>
                                    <div class="mb-3">
                                        <input type="submit" class="btn btn-primary" value="Zarejestruj">
                                        <a class="btn btn-secondary" href="index.php">Anuluj</a>
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
