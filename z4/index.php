<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

/* Rejestracja wizyty goscia portalu (templatka z3) */
zapisz_wizyte($conn);

$uid = (int)($_SESSION['id'] ?? 0);
$admin = is_admin();
$where = $admin ? "1=1" : "user_id = $uid";

/* Statystyki monitorowanych uslug */
$stats = ['total' => 0, 'down' => 0, 'tot_errors' => 0];
$res = mysqli_query($conn, "SELECT COUNT(*) AS total,
                                   SUM(CASE WHEN errors > 0 THEN 1 ELSE 0 END) AS down,
                                   SUM(total_errors) AS tot_errors
                            FROM domeny WHERE $where");
if ($res && ($row = mysqli_fetch_assoc($res))) {
    $stats['total']      = (int)$row['total'];
    $stats['down']       = (int)$row['down'];
    $stats['tot_errors'] = (int)$row['tot_errors'];
}

$users_count = 0;
if ($admin) {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM users");
    if ($r && ($x = mysqli_fetch_assoc($r))) $users_count = (int)$x['c'];
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
            <div class="container-fluid">
                <h2>Zadanie 4 (z4) - Monitorowanie usług w sieciach IP</h2>
                <p>
                    Zalogowany jako: <b><?php echo h($_SESSION['username'] ?? ''); ?></b>
                    <?php if ($admin): ?><span class="badge bg-warning text-dark">administrator</span><?php endif; ?>
                    &nbsp;|&nbsp; <a href="../index.php">&larr; Powrót do listy zadań</a>
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <div class="fs-3"><?php echo $stats['total']; ?></div>
                                <div class="small">monitorowane usługi</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-center border-danger">
                            <div class="card-body">
                                <div class="fs-3 text-danger"><?php echo $stats['down']; ?></div>
                                <div class="small">aktualnie w awarii</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card text-center border-secondary">
                            <div class="card-body">
                                <div class="fs-3"><?php echo $stats['tot_errors']; ?></div>
                                <div class="small">łączne nieudane próby</div>
                            </div>
                        </div>
                    </div>
                    <?php if ($admin): ?>
                    <div class="col-6 col-md-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <div class="fs-3"><?php echo $users_count; ?></div>
                                <div class="small">użytkownicy portalu</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <h4>Funkcje aplikacji</h4>
                <div class="list-group mb-3">
                    <a class="list-group-item list-group-item-action" href="index1.php">
                        <b>1. Lista monitorowanych usług</b> - sprawdzanie dostępności hostów/portów (DNS + rzeczywista odpowiedź usługi), automatyczne odświeżanie co 10 s, ikony SVG i dźwięk awarii
                    </a>
                    <a class="list-group-item list-group-item-action" href="dodaj1.php">
                        <b>2. Dodaj host / port</b> - formularz dodający nową usługę do monitorowania (INSERT)
                    </a>
                    <a class="list-group-item list-group-item-action" href="kasuj1.php">
                        <b>3. Usuń host / port</b> - formularz usuwający usługę z monitorowania (DELETE)
                    </a>
                    <a class="list-group-item list-group-item-action" href="goscie.php">
                        <b>4. Goście portalu</b> - rejestr wizyt (templatka z3)
                    </a>
                    <?php if ($admin): ?>
                    <a class="list-group-item list-group-item-action" href="staff.php">
                        <b>5. Pracownicy</b> - przypisywanie hostów do pracowników odpowiedzialnych za utrzymanie (tylko admin)
                    </a>
                    <?php endif; ?>
                </div>

                <?php if ($admin): ?>
                <div class="alert alert-info">Jako administrator widzisz usługi <b>wszystkich</b> użytkowników. Zwykły użytkownik widzi wyłącznie swoje hosty.</div>
                <?php else: ?>
                <div class="alert alert-info">Widzisz wyłącznie hosty przypisane do Twojego konta (kolumna <code>user_id</code>).</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
