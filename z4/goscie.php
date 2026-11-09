<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$rezultat = mysqli_query($conn, "SELECT * FROM goscieportalu ORDER BY datetime DESC LIMIT 100");
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
                <h3>Goście portalu - rejestr wizyt (templatka z3)</h3>
                <p><a href="index.php">&larr; Panel z4</a> | <a href="index1.php">Lista monitorowanych usług</a></p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm" style="font-size:12px;">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>IP</th>
                                <th>Data i godzina</th>
                                <th>Przeglądarka</th>
                                <th>Ekran</th>
                                <th>Okno</th>
                                <th>Kolory</th>
                                <th>Ciasteczka</th>
                                <th>Java</th>
                                <th>Język</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($rezultat && mysqli_num_rows($rezultat) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($rezultat)): ?>
                                <tr>
                                    <td><?php echo (int)$row['id']; ?></td>
                                    <td><?php echo h($row['ipaddress']); ?></td>
                                    <td><?php echo h($row['datetime']); ?></td>
                                    <td><?php echo h($row['browser'] ?? ''); ?></td>
                                    <td><?php echo $row['screen_width'] !== null ? ((int)$row['screen_width'] . 'x' . (int)$row['screen_height']) : '-'; ?></td>
                                    <td><?php echo $row['window_width'] !== null ? ((int)$row['window_width'] . 'x' . (int)$row['window_height']) : '-'; ?></td>
                                    <td><?php echo $row['color_depth'] !== null ? (int)$row['color_depth'] : '-'; ?></td>
                                    <td><?php echo $row['cookies_enabled'] !== null ? ($row['cookies_enabled'] ? 'Tak' : 'Nie') : '-'; ?></td>
                                    <td><?php echo $row['java_enabled'] !== null ? ($row['java_enabled'] ? 'Tak' : 'Nie') : '-'; ?></td>
                                    <td><?php echo h($row['language'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center">Brak zarejestrowanych wizyt.</td></tr>
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
