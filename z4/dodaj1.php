<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$komunikat = $_GET['msg'] ?? '';
$blad = $_GET['err'] ?? '';

$admin = is_admin();
$uid = (int)($_SESSION['id'] ?? 0);
$where = $admin ? "1=1" : "user_id = $uid";
$lista = mysqli_query($conn, "SELECT d.id, d.host, d.port, u.username AS owner
                              FROM domeny d LEFT JOIN users u ON u.id = d.user_id
                              WHERE $where ORDER BY d.id");
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
                <h3>Dodaj host / port do monitorowania</h3>
                <p><a href="index.php">&larr; Panel z4</a> | <a href="index1.php">Lista monitorowanych usług</a></p>

                <?php if ($komunikat !== ''): ?>
                    <div class="alert alert-success"><?php echo h($komunikat); ?></div>
                <?php endif; ?>
                <?php if ($blad !== ''): ?>
                    <div class="alert alert-danger"><?php echo h($blad); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" action="dodaj2.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label for="host" class="form-label">Host (nazwa domenowa lub adres IP)</label>
                                    <input type="text" class="form-control" id="host" name="host" maxlength="255" placeholder="np. fb.com lub 192.0.2.1" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="port" class="form-label">Port (usługa)</label>
                                    <input type="number" class="form-control" id="port" name="port" min="1" max="65535" value="80" required>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn btn-primary">Dodaj do monitorowania</button>
                                </div>
                            </div>
                            <div class="form-text mt-2">Dodawany jest zarówno adres hosta, jak i numer portu - na tym samym IP może działać kilka usług.</div>
                        </form>
                    </div>
                </div>

                <h5>Obecnie monitorowane usługi<?php echo $admin ? ' (wszyscy użytkownicy)' : ''; ?>:</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped">
                        <thead class="table-dark"><tr><th>ID</th><th>Host</th><th>Port</th><th>Właściciel</th></tr></thead>
                        <tbody>
                        <?php if ($lista && mysqli_num_rows($lista) > 0): ?>
                            <?php while ($w = mysqli_fetch_assoc($lista)): ?>
                                <tr>
                                    <td><?php echo (int)$w['id']; ?></td>
                                    <td><?php echo h($w['host']); ?></td>
                                    <td><?php echo (int)$w['port']; ?></td>
                                    <td><?php echo h($w['owner'] ?? '-'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Brak wpisów.</td></tr>
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
