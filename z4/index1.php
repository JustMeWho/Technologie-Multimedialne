<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_once "monitor.php";
require_login();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

/* Co ile sekund strona odswieza sie automatycznie (prymitywna metoda z instrukcji) */
define('ODSWIEZANIE_S', 10);
/* Po ilu sekundach awarii ikona zaczyna pulsowac */
define('PROG_PULSOWANIA_S', 20);
/* Po ilu sekundach awarii uruchamiany jest dzwiek */
define('PROG_DZWIEKU_S', 40);

$admin = is_admin();
$uid = (int)($_SESSION['id'] ?? 0);

/* Wybor hostow: administrator widzi wszystkie, zwykly uzytkownik tylko swoje */
if ($admin) {
    $sql = "SELECT d.*, u.username AS owner, w.username AS worker
            FROM domeny d
            LEFT JOIN users u ON u.id = d.user_id
            LEFT JOIN users w ON w.id = d.worker_id
            ORDER BY d.id";
} else {
    $sql = "SELECT d.*, u.username AS owner, w.username AS worker
            FROM domeny d
            LEFT JOIN users u ON u.id = d.user_id
            LEFT JOIN users w ON w.id = d.worker_id
            WHERE d.user_id = $uid
            ORDER BY d.id";
}
$rezultat = mysqli_query($conn, $sql);
$hosty = [];
if ($rezultat) {
    while ($w = mysqli_fetch_assoc($rezultat)) {
        $hosty[] = $w;
    }
}

/* Przygotowanie zapytan aktualizujacych stan monitoringu */
$stmt_fail = mysqli_prepare($conn,
    "UPDATE domeny SET errors = errors + 1, total_errors = total_errors + 1,
            failure = IFNULL(failure, ?) WHERE id = ?");
$stmt_ok = mysqli_prepare($conn,
    "UPDATE domeny SET errors = 0, failure = NULL WHERE id = ?");

/* Sprawdzenie kazdego hosta: DNS + rzeczywista odpowiedz uslugi */
$now = time();
$nowStr = date('Y-m-d H:i:s', $now);
foreach ($hosty as &$w) {
    $idv = (int)$w['id'];
    $host = $w['host'];
    $port = (int)$w['port'];

    $check = monitor_check((string)$host, $port);

    if (!empty($check['online'])) {
        $w['online'] = true;
        $w['errno'] = 0;
        $w['errstr'] = '';
        /* Host odpowiada - jesli wczesniej byl w awarii, zerujemy licznik serii i czas awarii */
        if ((int)$w['errors'] !== 0 || $w['failure'] !== null) {
            if ($stmt_ok) {
                mysqli_stmt_bind_param($stmt_ok, 'i', $idv);
                mysqli_stmt_execute($stmt_ok);
            }
            $w['errors'] = 0;
            $w['failure'] = null;
        }
    } else {
        $w['online'] = false;
        $w['errno'] = (int)($check['errno'] ?? 0);
        $w['errstr'] = (string)($check['errstr'] ?? 'Usluga nie odpowiada');
        /* Host nie odpowiada - inkrementujemy licznik serii oraz licznik laczny */
        if ($stmt_fail) {
            mysqli_stmt_bind_param($stmt_fail, 'si', $nowStr, $idv);
            mysqli_stmt_execute($stmt_fail);
        }
        $w['errors'] = (int)$w['errors'] + 1;
        $w['total_errors'] = (int)$w['total_errors'] + 1;
        if ($w['failure'] === null) {
            $w['failure'] = date('Y-m-d H:i:s', $now);
        }
    }

    /* Czas trwania biezacej awarii (od pierwszego nieudanego polaczenia w serii) */
    $failTs = $w['failure'] !== null ? strtotime((string)$w['failure']) : 0;
    $w['down_sec'] = ($failTs > 0 && !$w['online']) ? max(0, $now - $failTs) : 0;
    /* Laczny czas niedostepnosci = liczba nieudanych prob * interwal odswiezania */
    $w['total_down_sec'] = (int)$w['total_errors'] * ODSWIEZANIE_S;
}
unset($w);

if ($stmt_fail) mysqli_stmt_close($stmt_fail);
if ($stmt_ok)   mysqli_stmt_close($stmt_ok);

/* Czy wystepuje awaria > 40 s (uruchamiajaca dzwiek)? */
$alarm = false;
$liczba_awarii = 0;
foreach ($hosty as $w) {
    if (!$w['online']) {
        $liczba_awarii++;
        if ($w['down_sec'] > PROG_DZWIEKU_S) $alarm = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="refresh" content="<?php echo ODSWIEZANIE_S; ?>">
    <title>125340</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="twoj_js.js"></script>
</head>
<body onload="myLoadHeader()">
    <div id='myHeader'></div>
    <?php if ($alarm): ?>
    <!-- Znacznik dla skryptu JS: co najmniej jedna awaria trwa dluzej niz 40 s -->
    <div id="z4Alarm" data-alert="1" class="d-none"></div>
    <?php endif; ?>
    <main>
        <section class="sekcja1">
            <div class="container-fluid">
                <h3>Monitorowane usługi sieciowe</h3>
                <p>
                    <a href="index.php">&larr; Panel z4</a> |
                    <a href="dodaj1.php">Dodaj host/port</a> |
                    <a href="kasuj1.php">Usuń host/port</a> |
                    <a href="index1.php">Odśwież teraz</a>
                    &nbsp;|&nbsp; automatyczne odświeżanie co <b><?php echo ODSWIEZANIE_S; ?> s</b>
                    <?php if ($admin): ?>&nbsp;|&nbsp; <span class="badge bg-warning text-dark">widok administratora (wszystkie hosty)</span><?php endif; ?>
                </p>

                <?php if ($liczba_awarii > 0): ?>
                    <div class="alert alert-danger py-2">Wykryto awarie usług: <b><?php echo $liczba_awarii; ?></b>.
                        <?php if ($alarm): ?>Awaria trwa ponad <?php echo PROG_DZWIEKU_S; ?> s - uruchomiono ostrzeganie dźwiękowe.<?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-2">Wszystkie monitorowane usługi odpowiadają.</div>
                <?php endif; ?>

                <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm table-monitor">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Usługa</th>
                            <th>Stan</th>
                            <th>Błędów z rzędu</th>
                            <th>Czas bieżącej awarii</th>
                            <th>Data utraty komunikacji</th>
                            <th>Łączne błędy</th>
                            <th>Łączny czas niedostępności</th>
                            <th>Właściciel</th>
                            <th>Pracownik</th>
                            <th>Akcja</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($hosty) === 0): ?>
                        <tr><td colspan="13" class="text-center">Brak monitorowanych hostów. Dodaj pierwszy host w zakładce <a href="dodaj1.php">Dodaj host/port</a>.</td></tr>
                    <?php else: ?>
                    <?php foreach ($hosty as $w): ?>
                        <?php
                            $online = $w['online'];
                            $pulse  = (!$online && $w['down_sec'] > PROG_PULSOWANIA_S);
                            $ikona  = service_icon((int)$w['port'], $online);
                            $klasa  = 'service-icon' . ($pulse ? ' icon-alarm-pulse' : '');
                            $rowcls = $online ? '' : 'row-down';
                        ?>
                        <tr class="<?php echo $rowcls; ?>"<?php echo (!$online && $w['down_sec'] > PROG_DZWIEKU_S) ? ' data-alert="1"' : ''; ?>>
                            <td><?php echo (int)$w['id']; ?></td>
                            <td><?php echo h($w['host']); ?></td>
                            <td><?php echo (int)$w['port']; ?></td>
                            <td><img src="<?php echo h($ikona); ?>" class="<?php echo $klasa; ?>" alt="ikona usługi" title="<?php echo h(service_name((int)$w['port'])); ?>"> <?php echo h(service_name((int)$w['port'])); ?></td>
                            <td>
                                <?php if ($online): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Awaria</span>
                                    <div class="small text-danger"><?php echo (int)$w['errno']; ?> <?php echo h($w['errstr']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo (int)$w['errors']; ?></td>
                            <td>
                                <?php if (!$online): ?>
                                    <?php echo format_hms((int)$w['down_sec']); ?>
                                    <?php if ($w['down_sec'] > PROG_DZWIEKU_S): ?>
                                        <span class="badge bg-danger">ALARM</span>
                                    <?php elseif ($w['down_sec'] > PROG_PULSOWANIA_S): ?>
                                        <span class="badge bg-warning text-dark">awaria &gt; <?php echo PROG_PULSOWANIA_S; ?> s</span>
                                    <?php endif; ?>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?php echo $w['failure'] !== null ? h($w['failure']) : '-'; ?></td>
                            <td class="text-end"><?php echo (int)$w['total_errors']; ?></td>
                            <td><?php echo format_hms((int)$w['total_down_sec']); ?></td>
                            <td><?php echo h($w['owner'] ?? '-'); ?></td>
                            <td><?php echo h($w['worker'] ?? '-'); ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-danger" href="kasuj1.php?host=<?php echo urlencode($w['host']); ?>&amp;port=<?php echo (int)$w['port']; ?>">Usuń</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>

                <div class="legenda small mt-2">
                    <b>Legenda:</b>
                    <img src="media/icons/web_online.svg" alt="online"> usługa działa (statyczna, zielona) &nbsp;
                    <img src="media/icons/web_offline.svg" alt="offline"> awaria do <?php echo PROG_PULSOWANIA_S; ?> s (statyczna, czerwona) &nbsp;
                    <img src="media/icons/web_offline.svg" class="icon-alarm-pulse" alt="puls"> awaria &gt; <?php echo PROG_PULSOWANIA_S; ?> s (pulsuje) &nbsp;
                    awaria &gt; <?php echo PROG_DZWIEKU_S; ?> s dodatkowo uruchamia dźwięk (konfiguracja w stopce).
                </div>
                <p class="small text-muted mt-2">
                    Uwaga: hosting InfinityFree proxuje połączenia wychodzące, dlatego samo <code>fsockopen()</code>
                    mogło raportować nieistniejące domeny jako dostępne. Monitor najpierw sprawdza DNS, a następnie
                    czeka na rzeczywistą odpowiedź usługi (HTTP, banner FTP/SMTP/SSH itp.). Dzięki temu losowa domena
                    jest wykrywana jako awaria. Do demonstracji awarii nadal można użyć adresu testowego
                    <code>192.0.2.1</code> (pula TEST-NET-1, nieosiągalny). Ilość błędów nalicza się przy każdym
                    odświeżeniu strony - przy otwarciu portalu na kilku urządzeniach może rosnąć szybciej (wada opisana w instrukcji).
                </p>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
