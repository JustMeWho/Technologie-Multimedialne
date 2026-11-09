<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();
if (!is_admin()) {
    header('Location: index.php');
    exit();
}

$komunikat = $_GET['msg'] ?? '';
$blad = $_GET['err'] ?? '';
$admin_id = (int)($_SESSION['id'] ?? 0);

/* Pracownicy = uzytkownicy z kolumna worker = 1 */
$workers = mysqli_query($conn, "SELECT id, username FROM users WHERE worker = 1 ORDER BY username");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? '');
    $port = (int)($_POST['port'] ?? 0);
    $worker_id = (int)($_POST['worker_id'] ?? 0);

    if ($host === '' || $port < 1 || $port > 65535 || $worker_id <= 0) {
        header('Location: staff.php?err=' . urlencode('Uzupełnij host, port oraz wybierz pracownika.'));
        exit();
    }
    /* Sprawdzamy, czy wybrany uzytkownik faktycznie jest pracownikiem */
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? AND worker = 1");
    $valid = false;
    if ($chk) {
        mysqli_stmt_bind_param($chk, 'i', $worker_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $valid = (mysqli_stmt_num_rows($chk) === 1);
        mysqli_stmt_close($chk);
    }
    if (!$valid) {
        header('Location: staff.php?err=' . urlencode('Wybrany użytkownik nie jest pracownikiem.'));
        exit();
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO domeny (host, port, user_id, worker_id) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'siii', $host, $port, $admin_id, $worker_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        if ($ok) {
            header('Location: staff.php?msg=' . urlencode('Dodano ' . $host . ':' . $port . ' i przypisano pracownika.'));
            exit();
        }
    }
    mysqli_close($conn);
    header('Location: staff.php?err=' . urlencode('Nie udało się dodać wpisu.'));
    exit();
}

/* Lista hostow wraz z przypisanym pracownikiem */
$lista = mysqli_query($conn, "SELECT d.id, d.host, d.port, u.username AS owner, w.username AS worker
                              FROM domeny d
                              LEFT JOIN users u ON u.id = d.user_id
                              LEFT JOIN users w ON w.id = d.worker_id
                              ORDER BY d.id");
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
                <h3>Pracownicy - przypisywanie hostów do odpowiedzialnych osób</h3>
                <p><a href="index.php">&larr; Panel z4</a> | <a href="index1.php">Lista monitorowanych usług</a></p>

                <?php if ($komunikat !== ''): ?>
                    <div class="alert alert-success"><?php echo h($komunikat); ?></div>
                <?php endif; ?>
                <?php if ($blad !== ''): ?>
                    <div class="alert alert-danger"><?php echo h($blad); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" action="staff.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label for="host" class="form-label">Host</label>
                                    <input type="text" class="form-control" id="host" name="host" maxlength="255" placeholder="np. fb.com" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="port" class="form-label">Port</label>
                                    <input type="number" class="form-control" id="port" name="port" min="1" max="65535" value="80" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="worker_id" class="form-label">Pracownik odpowiedzialny</label>
                                    <select class="form-select" id="worker_id" name="worker_id" required>
                                        <option value="">-- wybierz pracownika --</option>
                                        <?php if ($workers && mysqli_num_rows($workers) > 0): ?>
                                            <?php while ($p = mysqli_fetch_assoc($workers)): ?>
                                                <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['username']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary">Dodaj i przypisz</button>
                                </div>
                            </div>
                            <div class="form-text mt-2">Na liście pojawiają się wyłącznie użytkownicy z tabeli <code>users</code>, którzy mają ustawioną kolumnę <code>worker = 1</code>.</div>
                        </form>
                    </div>
                </div>

                <h5>Przypisania hostów do pracowników:</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped">
                        <thead class="table-dark"><tr><th>ID</th><th>Host</th><th>Port</th><th>Właściciel</th><th>Pracownik</th></tr></thead>
                        <tbody>
                        <?php if ($lista && mysqli_num_rows($lista) > 0): ?>
                            <?php while ($w = mysqli_fetch_assoc($lista)): ?>
                                <tr>
                                    <td><?php echo (int)$w['id']; ?></td>
                                    <td><?php echo h($w['host']); ?></td>
                                    <td><?php echo (int)$w['port']; ?></td>
                                    <td><?php echo h($w['owner'] ?? '-'); ?></td>
                                    <td><?php echo $w['worker'] !== null ? '<b>' . h($w['worker']) . '</b>' : '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">Brak wpisów.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
