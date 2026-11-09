<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
$admin = is_admin();
$rows = [];

if ($admin) {
    $rezultat = mysqli_query($conn,
        "SELECT id, ipaddress, datetime, browser, language, username, result
         FROM goscieportalu ORDER BY datetime DESC LIMIT 200");
    if ($rezultat) {
        while ($row = mysqli_fetch_assoc($rezultat)) $rows[] = $row;
    }
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT id, ipaddress, datetime, browser, language, username, result
         FROM goscieportalu WHERE username = ? ORDER BY datetime DESC LIMIT 200");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $r_id, $r_ip, $r_dt, $r_br, $r_lang, $r_uname, $r_res);
        while (mysqli_stmt_fetch($stmt)) {
            $rows[] = [
                'id' => $r_id, 'ipaddress' => $r_ip, 'datetime' => $r_dt,
                'browser' => $r_br, 'language' => $r_lang,
                'username' => $r_uname, 'result' => $r_res,
            ];
        }
        mysqli_stmt_close($stmt);
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
            <div class="container-fluid">
                <h3>Rejestr logowań (z5)</h3>
                <p>
                    <a href="index.php">&larr; Mój dysk</a>
                    <?php if ($admin): ?>
                        | Widok administratora: <b>wszystkie</b> konta.
                    <?php else: ?>
                        | Widok: wyłącznie Twoje próby logowania (<b><?php echo h($user); ?></b>).
                    <?php endif; ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm" style="font-size:12px;">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Data i godzina</th>
                                <th>Login</th>
                                <th>Wynik</th>
                                <th>Adres IP</th>
                                <th>Przeglądarka</th>
                                <th>Język</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($rows) > 0): ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                    $res = $row['result'];
                                    if ($res === 'success') {
                                        $badge = '<span class="badge bg-success">pomyślne</span>';
                                    } elseif ($res === 'failure') {
                                        $badge = '<span class="badge bg-danger">błędne</span>';
                                    } else {
                                        $badge = '<span class="badge bg-secondary">wizyta</span>';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo (int)$row['id']; ?></td>
                                    <td><?php echo h($row['datetime']); ?></td>
                                    <td><?php echo h($row['username'] ?? '-'); ?></td>
                                    <td><?php echo $badge; ?></td>
                                    <td><?php echo h($row['ipaddress']); ?></td>
                                    <td><?php echo h($row['browser'] ?? '-'); ?></td>
                                    <td><?php echo h($row['language'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">Brak zarejestrowanych logowań.</td></tr>
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
