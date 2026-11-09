<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) { http_response_code(403); die("Nieprawidłowa nazwa użytkownika."); }

/* Cofnięcie udostępnienia przez właściciela */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM udostepnienia WHERE id = ? AND owner = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $id, $user);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['flash_msg'] = "Cofnięto udostępnianie.";
            } else {
                $_SESSION['flash_err'] = "Nie znaleziono udostępnienia do cofnięcia.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("location: udostepnianie.php");
    exit;
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

/* Informacje o udostępnionym elemencie (istnienie, typ, rozmiar) */
function share_element_info(string $owner, string $path): array
{
    $out = ['exists' => false, 'dir' => false, 'size' => 0, 'mtime' => 0, 'name' => basename($path)];
    if (!valid_username_dir($owner)) return $out;
    $home  = home_abs($owner);
    $rhome = realpath($home);
    $real  = realpath($home . '/' . $path);
    if ($real === false || $rhome === false) return $out;
    if ($real !== $rhome && strpos($real, $rhome . DIRECTORY_SEPARATOR) !== 0) return $out;
    $out['exists'] = true;
    $out['dir']    = is_dir($real);
    $out['mtime']  = (int)@filemtime($real);
    $out['name']   = basename($real);
    if (is_file($real)) $out['size'] = (int)@filesize($real);
    return $out;
}

/* Udostępnienia otrzymane przez zalogowanego użytkownika */
$received = [];
$stmt = mysqli_prepare($conn,
    "SELECT id, owner, path, created_at FROM udostepnienia
     WHERE target = ? ORDER BY created_at DESC, id DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $r_id, $r_owner, $r_path, $r_created);
    while (mysqli_stmt_fetch($stmt)) {
        $received[] = [
            'id' => (int)$r_id, 'owner' => (string)$r_owner,
            'path' => (string)$r_path, 'created_at' => (string)$r_created,
            'info' => share_element_info((string)$r_owner, (string)$r_path),
        ];
    }
    mysqli_stmt_close($stmt);
}

/* Udostępnienia przyznane przez zalogowanego użytkownika */
$given = [];
$stmt = mysqli_prepare($conn,
    "SELECT id, owner, path, target, created_at FROM udostepnienia
     WHERE owner = ? ORDER BY created_at DESC, id DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $g_id, $g_owner, $g_path, $g_target, $g_created);
    while (mysqli_stmt_fetch($stmt)) {
        $given[] = [
            'id' => (int)$g_id, 'owner' => (string)$g_owner,
            'path' => (string)$g_path, 'target' => (string)$g_target,
            'created_at' => (string)$g_created,
            'info' => share_element_info((string)$g_owner, (string)$g_path),
        ];
    }
    mysqli_stmt_close($stmt);
}

/* Podgląd zawartości udostępnionego katalogu */
$view_share = null;
$sub = '';
$sub_crumbs = [];
$shared_entries = [];
$share_id = (int)($_GET['share'] ?? 0);

if ($share_id > 0) {
    $view_share = get_share_for_target($conn, $share_id, $user);
    if ($view_share === null) {
        $_SESSION['flash_err'] = "Nie znaleziono udostępnienia.";
        header("location: udostepnianie.php");
        exit;
    }
    $share_path = norm_rel($view_share['path']);
    $sub = norm_rel((string)($_GET['dir'] ?? ''));
    if ($share_path === null || $share_path === '' || $sub === null) {
        $_SESSION['flash_err'] = "Nieprawidłowa ścieżka udostępnionego katalogu.";
        header("location: udostepnianie.php");
        exit;
    }
    $full = ($sub === '') ? $share_path : $share_path . '/' . $sub;
    $abs = dir_abs($view_share['owner'], $full);
    if ($abs === null) {
        $_SESSION['flash_err'] = "Udostępniony katalog nie istnieje lub został usunięty.";
        header("location: udostepnianie.php");
        exit;
    }

    $scan = @scandir($abs);
    if ($scan !== false) {
        foreach ($scan as $n) {
            if ($n === '.' || $n === '..') continue;
            $p = $abs . '/' . $n;
            $shared_entries[] = [
                'name'  => $n,
                'dir'   => is_dir($p),
                'size'  => is_file($p) ? (int)@filesize($p) : 0,
                'mtime' => (int)@filemtime($p),
                'rel'   => ($sub === '') ? $n : $sub . '/' . $n,
                'full'  => ($full === '') ? $n : $full . '/' . $n,
            ];
        }
    }
    usort($shared_entries, function ($a, $b) {
        if ($a['dir'] !== $b['dir']) return $a['dir'] ? -1 : 1;
        return strnatcasecmp($a['name'], $b['name']);
    });

    if ($sub !== '') {
        $acc = '';
        foreach (explode('/', $sub) as $seg) {
            $acc = ($acc === '') ? $seg : $acc . '/' . $seg;
            $sub_crumbs[] = ['name' => $seg, 'rel' => $acc];
        }
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
                <h3>Udostępnianie plików i katalogów (z5)</h3>
                <p class="mb-2">
                    Zalogowany jako: <b><?php echo h($user); ?></b>
                    &nbsp;|&nbsp; <a href="index.php">&larr; Mój dysk</a>
                </p>

                <?php if ($flash_msg !== ''): ?>
                    <div class="alert alert-success"><?php echo h($flash_msg); ?></div>
                <?php endif; ?>
                <?php if ($flash_err !== ''): ?>
                    <div class="alert alert-danger"><?php echo h($flash_err); ?></div>
                <?php endif; ?>

                <?php if ($view_share !== null): ?>
                    <?php $share_path = (string)$view_share['path']; ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span>
                                Zawartość udostępnionego katalogu &mdash;
                                <b><?php echo h($view_share['owner']); ?></b>:
                                <code><?php echo h($share_path); ?></code>
                            </span>
                            <a class="btn btn-sm btn-outline-secondary" href="udostepnianie.php">Zamknij podgląd</a>
                        </div>
                        <div class="card-body">
                            <div class="fm-breadcrumb mb-2">
                                <a href="udostepnianie.php?share=<?php echo (int)$view_share['id']; ?>">
                                    <img src="media/icons/folder.svg" width="18" height="18" alt=""> <?php echo h(basename($share_path)); ?>
                                </a>
                                <?php foreach ($sub_crumbs as $c): ?>
                                    <span class="sep">/</span>
                                    <a href="udostepnianie.php?share=<?php echo (int)$view_share['id']; ?>&amp;dir=<?php echo rawurlencode($c['rel']); ?>"><?php echo h($c['name']); ?></a>
                                <?php endforeach; ?>
                            </div>

                            <div class="fm-toolbar mb-3">
                                <?php if ($sub !== ''): ?>
                                    <?php $parent = implode('/', array_slice(explode('/', $sub), 0, -1)); ?>
                                    <a class="btn btn-sm btn-outline-primary" href="udostepnianie.php?share=<?php echo (int)$view_share['id']; ?><?php echo $parent !== '' ? '&amp;dir=' . rawurlencode($parent) : ''; ?>">
                                        <img src="media/icons/up.svg" width="18" height="18" alt=""> Poziom wyżej
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if (count($shared_entries) === 0): ?>
                                <div class="alert alert-info mb-0">Ten udostępniony katalog jest pusty.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-sm fm-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nazwa</th>
                                                <th>Typ</th>
                                                <th>Rozmiar</th>
                                                <th>Data modyfikacji</th>
                                                <th>Akcje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($shared_entries as $e): ?>
                                            <tr>
                                                <td class="fm-name">
                                                    <?php if ($e['dir']): ?>
                                                        <img class="fm-kind" src="media/icons/folder.svg" alt="">
                                                        <a class="fm-dir" href="udostepnianie.php?share=<?php echo (int)$view_share['id']; ?>&amp;dir=<?php echo rawurlencode($e['rel']); ?>"><?php echo h($e['name']); ?></a>
                                                    <?php else: ?>
                                                        <img class="fm-kind" src="media/icons/file.svg" alt="">
                                                        <?php echo h($e['name']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $e['dir'] ? 'katalog' : h(strtoupper(pathinfo($e['name'], PATHINFO_EXTENSION)) ?: 'plik'); ?></td>
                                                <td><?php echo $e['dir'] ? '-' : h(format_bytes($e['size'])); ?></td>
                                                <td><?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></td>
                                                <td>
                                                    <?php if ($e['dir']): ?>
                                                        <a class="btn btn-sm btn-outline-primary" href="udostepnianie.php?share=<?php echo (int)$view_share['id']; ?>&amp;dir=<?php echo rawurlencode($e['rel']); ?>">Otwórz</a>
                                                    <?php else: ?>
                                                        <a class="btn btn-sm btn-outline-primary" href="download.php?owner=<?php echo rawurlencode((string)$view_share['owner']); ?>&amp;path=<?php echo rawurlencode($e['full']); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <h4>Udostępnione mi</h4>
                <div class="table-responsive mb-4">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Właściciel</th>
                                <th>Element</th>
                                <th>Typ</th>
                                <th>Udostępniono</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($received) > 0): ?>
                            <?php foreach ($received as $s): ?>
                                <?php $info = $s['info']; ?>
                                <tr>
                                    <td><b><?php echo h($s['owner']); ?></b></td>
                                    <td class="fm-name"><code><?php echo h($s['path']); ?></code></td>
                                    <td>
                                        <?php if (!$info['exists']): ?>
                                            <span class="badge bg-secondary">niedostępny</span>
                                        <?php elseif ($info['dir']): ?>
                                            <span class="badge bg-primary">katalog</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">plik</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo h($s['created_at']); ?></td>
                                    <td>
                                        <?php if ($info['exists'] && $info['dir']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="udostepnianie.php?share=<?php echo (int)$s['id']; ?>"><img src="media/icons/folder.svg" width="16" height="16" alt=""> Otwórz katalog</a>
                                        <?php elseif ($info['exists']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="download.php?owner=<?php echo rawurlencode($s['owner']); ?>&amp;path=<?php echo rawurlencode($s['path']); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                        <?php else: ?>
                                            <span class="text-muted small">element usunięty</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">Nikt nie udostępnił Ci jeszcze plików ani katalogów.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h4>Udostępnione przeze mnie</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Odbiorca</th>
                                <th>Element</th>
                                <th>Typ</th>
                                <th>Udostępniono</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($given) > 0): ?>
                            <?php foreach ($given as $s): ?>
                                <?php $info = $s['info']; ?>
                                <tr>
                                    <td><b><?php echo h($s['target']); ?></b></td>
                                    <td class="fm-name"><code><?php echo h($s['path']); ?></code></td>
                                    <td>
                                        <?php if (!$info['exists']): ?>
                                            <span class="badge bg-secondary">niedostępny</span>
                                        <?php elseif ($info['dir']): ?>
                                            <span class="badge bg-primary">katalog</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">plik</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo h($s['created_at']); ?></td>
                                    <td>
                                        <form action="udostepnianie.php" method="post" class="d-inline js-confirm" data-confirm="Cofnąć udostępnienie elementu „<?php echo h($s['path']); ?>” użytkownikowi <?php echo h($s['target']); ?>?">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cofnij udostępnianie</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">Nie udostępniłeś jeszcze żadnych plików ani katalogów.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small mt-3">
                    Udostępnić można plik lub katalog z własnego dysku. Odbiorca widzi element na tej stronie
                    i może pobrać plik albo przeglądać i pobierać zawartość udostępnionego katalogu.
                    Udostępnienie nie daje prawa do usuwania ani modyfikowania plików właściciela.
                </p>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
