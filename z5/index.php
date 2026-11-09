<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */ ?>
<?php
require_once "db_config.php";
require_login();

$user = (string)($_SESSION['username'] ?? '');
if (!ensure_home($user)) {
    die("Nie można utworzyć katalogu macierzystego użytkownika.");
}

/* Biezacy katalog (wzgledem katalogu macierzystego) */
$rel_raw = $_GET['dir'] ?? '';
$rel = norm_rel((string)$rel_raw);
if ($rel === null) {
    $_SESSION['flash_err'] = "Nieprawidłowa ścieżka katalogu.";
    header("location: index.php");
    exit;
}
$abs = dir_abs($user, $rel);
if ($abs === null) {
    $_SESSION['flash_err'] = "Katalog nie istnieje.";
    header("location: index.php");
    exit;
}
$depth = rel_depth($rel);

/* Tryb widoku: lista lub multimedia (zapamietywany w sesji) */
if (isset($_GET['view']) && in_array($_GET['view'], ['list', 'media'], true)) {
    $_SESSION['z5_view'] = $_GET['view'];
}
$view = $_SESSION['z5_view'] ?? 'media';

/* Komunikaty */
$login_warning = $_SESSION['login_warning'] ?? '';
unset($_SESSION['login_warning']);
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_err = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

/* Odczyt zawartosci katalogu */
$entries = [];
$scan = @scandir($abs);
if ($scan !== false) {
    foreach ($scan as $name) {
        if ($name === '.' || $name === '..') continue;
        $p = $abs . '/' . $name;
        $entries[] = [
            'name'  => $name,
            'dir'   => is_dir($p),
            'size'  => is_file($p) ? (int)@filesize($p) : 0,
            'mtime' => (int)@filemtime($p),
            'kind'  => is_file($p) ? file_kind($name) : 'dir',
        ];
    }
}
usort($entries, function ($a, $b) {
    if ($a['dir'] !== $b['dir']) return $a['dir'] ? -1 : 1;
    return strnatcasecmp($a['name'], $b['name']);
});

/* Pomocnicze budowanie adresow */
$enc_dir = rawurlencode($rel);
$view_q  = 'view=' . rawurlencode($view);
function entry_rel(string $cur, string $name): string {
    return $cur === '' ? $name : $cur . '/' . $name;
}
function crumbs(string $rel): array {
    $out = [];
    if ($rel === '') return $out;
    $acc = '';
    foreach (explode('/', $rel) as $seg) {
        $acc = $acc === '' ? $seg : $acc . '/' . $seg;
        $out[] = ['name' => $seg, 'rel' => $acc];
    }
    return $out;
}
$crumbs = crumbs($rel);
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
                <h2>MyCloud - mój dysk (z5)</h2>
                <p class="mb-2">
                    Zalogowany jako: <b><?php echo h($user); ?></b>
                    <?php if (is_admin()): ?><span class="badge bg-warning text-dark">administrator</span><?php endif; ?>
                    &nbsp;|&nbsp; <a href="../index.php">&larr; Powrót do listy zadań</a>
                </p>

                <?php if ($login_warning !== ''): ?>
                    <div class="alert alert-danger alert-break-in">
                        <b>Uwaga!</b> <?php echo h($login_warning); ?>
                        Zablokuj dostęp / zmień hasło, jeśli to nie Ty.
                    </div>
                <?php endif; ?>
                <?php if ($flash_msg !== ''): ?>
                    <div class="alert alert-success"><?php echo h($flash_msg); ?></div>
                <?php endif; ?>
                <?php if ($flash_err !== ''): ?>
                    <div class="alert alert-danger"><?php echo h($flash_err); ?></div>
                <?php endif; ?>

                <div class="fm-breadcrumb mb-2">
                    <a href="index.php?<?php echo $view_q; ?>"><img src="media/icons/folder.svg" width="18" height="18" alt=""> Dysk</a>
                    <?php foreach ($crumbs as $c): ?>
                        <span class="sep">/</span>
                        <a href="index.php?dir=<?php echo rawurlencode($c['rel']); ?>&amp;<?php echo $view_q; ?>"><?php echo h($c['name']); ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="fm-toolbar mb-3">
                    <?php if ($depth > 0): ?>
                        <?php $parent = implode('/', array_slice(explode('/', $rel), 0, -1)); ?>
                        <a class="btn btn-sm btn-outline-primary" href="index.php?dir=<?php echo rawurlencode($parent); ?>&amp;<?php echo $view_q; ?>">
                            <img src="media/icons/up.svg" width="18" height="18" alt=""> Poziom wyżej
                        </a>
                    <?php endif; ?>
                    <?php if ($depth < 4): ?>
                        <a class="btn btn-sm btn-success" href="mkdir.php?dir=<?php echo $enc_dir; ?>&amp;<?php echo $view_q; ?>">
                            <img src="media/icons/newfolder.svg" width="18" height="18" alt=""> Nowy katalog
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-primary" href="select.php?dir=<?php echo $enc_dir; ?>&amp;<?php echo $view_q; ?>">
                        <img src="media/icons/upload.svg" width="18" height="18" alt=""> Wgraj plik
                    </a>
                    <?php if ($view === 'media'): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="index.php?dir=<?php echo $enc_dir; ?>&amp;view=list">Widok: lista</a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-secondary" href="index.php?dir=<?php echo $enc_dir; ?>&amp;view=media">Widok: multimedia</a>
                    <?php endif; ?>
                </div>

                <?php if (count($entries) === 0): ?>
                    <div class="alert alert-info">Ten katalog jest pusty. Wgraj plik lub utwórz podkatalog.</div>
                <?php elseif ($view === 'list'): ?>
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
                            <?php foreach ($entries as $e): ?>
                                <?php $erel = entry_rel($rel, $e['name']); ?>
                                <tr>
                                    <td class="fm-name">
                                        <?php if ($e['dir']): ?>
                                            <img class="fm-kind" src="media/icons/folder.svg" alt="">
                                            <a class="fm-dir" href="index.php?dir=<?php echo rawurlencode($erel); ?>&amp;<?php echo $view_q; ?>"><?php echo h($e['name']); ?></a>
                                        <?php else: ?>
                                            <?php
                                                $icon = 'file.svg';
                                                if ($e['kind'] === 'image') $icon = 'image.svg';
                                                elseif ($e['kind'] === 'audio') $icon = 'audio.svg';
                                                elseif ($e['kind'] === 'video') $icon = 'video.svg';
                                            ?>
                                            <img class="fm-kind" src="media/icons/<?php echo $icon; ?>" alt="">
                                            <a href="download.php?path=<?php echo rawurlencode($erel); ?>"><?php echo h($e['name']); ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $e['dir'] ? 'katalog' : h(strtoupper(pathinfo($e['name'], PATHINFO_EXTENSION)) ?: 'plik'); ?></td>
                                    <td><?php echo $e['dir'] ? '-' : h(format_bytes($e['size'])); ?></td>
                                    <td><?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></td>
                                    <td>
                                        <?php if (!$e['dir']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="download.php?path=<?php echo rawurlencode($erel); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                        <?php endif; ?>
                                        <a class="btn btn-sm btn-outline-success" href="share.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/share.svg" width="16" height="16" alt=""> Udostępnij</a>
                                        <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Czy na pewno usunąć <?php echo $e['dir'] ? 'katalog (wraz z zawartością)' : 'plik'; ?>?" href="delete.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/trash.svg" width="16" height="16" alt=""> Usuń</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="fm-grid">
                    <?php foreach ($entries as $e): ?>
                        <?php $erel = entry_rel($rel, $e['name']); ?>
                        <div class="fm-card">
                            <?php if ($e['dir']): ?>
                                <a class="fm-thumb" href="index.php?dir=<?php echo rawurlencode($erel); ?>&amp;<?php echo $view_q; ?>">
                                    <img class="fm-big-icon" src="media/icons/folder.svg" alt="katalog">
                                </a>
                                <div class="fm-body">
                                    <div class="fm-title"><a class="fm-dir" href="index.php?dir=<?php echo rawurlencode($erel); ?>&amp;<?php echo $view_q; ?>"><?php echo h($e['name']); ?></a></div>
                                    <div class="fm-meta">katalog &middot; <?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></div>
                                    <div class="mt-2 d-flex gap-1">
                                        <a class="btn btn-sm btn-outline-success" href="share.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/share.svg" width="16" height="16" alt=""> Udostępnij</a>
                                        <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Czy na pewno usunąć katalog (wraz z zawartością)?" href="delete.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/trash.svg" width="16" height="16" alt=""> Usuń</a>
                                    </div>
                                </div>
                            <?php elseif ($e['kind'] === 'image'): ?>
                                <a class="fm-thumb" href="download.php?path=<?php echo rawurlencode($erel); ?>&amp;inline=1" target="_blank" rel="noopener">
                                    <img src="download.php?path=<?php echo rawurlencode($erel); ?>&amp;inline=1" alt="<?php echo h($e['name']); ?>">
                                </a>
                                <div class="fm-body">
                                    <div class="fm-title"><a href="download.php?path=<?php echo rawurlencode($erel); ?>"><?php echo h($e['name']); ?></a></div>
                                    <div class="fm-meta"><?php echo h(format_bytes($e['size'])); ?> &middot; <?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></div>
                                    <div class="mt-2 d-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="download.php?path=<?php echo rawurlencode($erel); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                        <a class="btn btn-sm btn-outline-success" href="share.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/share.svg" width="16" height="16" alt=""> Udostępnij</a>
                                        <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Czy na pewno usunąć plik?" href="delete.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/trash.svg" width="16" height="16" alt=""> Usuń</a>
                                    </div>
                                </div>
                            <?php elseif ($e['kind'] === 'audio'): ?>
                                <div class="fm-thumb"><img class="fm-big-icon" src="media/icons/audio.svg" alt="audio"></div>
                                <div class="fm-body">
                                    <div class="fm-title"><a href="download.php?path=<?php echo rawurlencode($erel); ?>"><?php echo h($e['name']); ?></a></div>
                                    <div class="fm-meta"><?php echo h(format_bytes($e['size'])); ?> &middot; <?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></div>
                                    <audio controls preload="none" src="download.php?path=<?php echo rawurlencode($erel); ?>&amp;inline=1"></audio>
                                    <div class="mt-2 d-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="download.php?path=<?php echo rawurlencode($erel); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                        <a class="btn btn-sm btn-outline-success" href="share.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/share.svg" width="16" height="16" alt=""> Udostępnij</a>
                                        <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Czy na pewno usunąć plik?" href="delete.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/trash.svg" width="16" height="16" alt=""> Usuń</a>
                                    </div>
                                </div>
                            <?php elseif ($e['kind'] === 'video'): ?>
                                <div class="fm-thumb">
                                    <video controls preload="none" src="download.php?path=<?php echo rawurlencode($erel); ?>&amp;inline=1"></video>
                                </div>
                                <div class="fm-body">
                                    <div class="fm-title"><a href="download.php?path=<?php echo rawurlencode($erel); ?>"><?php echo h($e['name']); ?></a></div>
                                    <div class="fm-meta"><?php echo h(format_bytes($e['size'])); ?> &middot; <?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></div>
                                    <div class="mt-2 d-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="download.php?path=<?php echo rawurlencode($erel); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                        <a class="btn btn-sm btn-outline-success" href="share.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/share.svg" width="16" height="16" alt=""> Udostępnij</a>
                                        <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Czy na pewno usunąć plik?" href="delete.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/trash.svg" width="16" height="16" alt=""> Usuń</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="fm-thumb"><img class="fm-big-icon" src="media/icons/file.svg" alt="plik"></div>
                                <div class="fm-body">
                                    <div class="fm-title"><a href="download.php?path=<?php echo rawurlencode($erel); ?>"><?php echo h($e['name']); ?></a></div>
                                    <div class="fm-meta"><?php echo h(format_bytes($e['size'])); ?> &middot; <?php echo h(date('Y-m-d H:i', $e['mtime'])); ?></div>
                                    <div class="mt-2 d-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="download.php?path=<?php echo rawurlencode($erel); ?>"><img src="media/icons/download.svg" width="16" height="16" alt=""> Pobierz</a>
                                        <a class="btn btn-sm btn-outline-success" href="share.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/share.svg" width="16" height="16" alt=""> Udostępnij</a>
                                        <a class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Czy na pewno usunąć plik?" href="delete.php?path=<?php echo rawurlencode($erel); ?>&amp;dir=<?php echo $enc_dir; ?>"><img src="media/icons/trash.svg" width="16" height="16" alt=""> Usuń</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="text-muted small mt-3">
                    Katalog macierzysty: <code>files/<?php echo h($user); ?></code>.
                    Można tworzyć podkatalogi do 4 poziomów zagnieżdżenia. Pliki innych użytkowników są dostępne wyłącznie po ich udostępnieniu (przycisk „Udostępnij”).
                    Maksymalny rozmiar wgrywanego pliku to <?php echo h(format_bytes(MAX_UPLOAD_BYTES)); ?> (limit hostingu InfinityFree).
                </p>
            </div>
        </section>
    </main>
    <?php require_once 'footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
