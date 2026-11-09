<?php
declare(strict_types=1);
require_once "db_config.php";
require_once "monitor.php";
require_login();
header('Content-Type: text/plain; charset=utf-8');

$tests = [
    ['google.com', 80, 'oczekiwane: ONLINE'],
    ['google.com', 443, 'oczekiwane: ONLINE'],
    ['wp.ppl', 80, 'oczekiwane: OFFLINE (brak DNS)'],
    ['nieistniejaca-domena-abc-987654.com', 80, 'oczekiwane: OFFLINE (brak DNS)'],
    ['192.0.2.1', 80, 'oczekiwane: OFFLINE (TEST-NET-1)'],
];

$host = trim((string)($_GET['host'] ?? ''));
$port = (int)($_GET['port'] ?? 80);
if ($host !== '') {
    $tests[] = [$host, $port, 'TEST WLASNY'];
}

foreach ($tests as [$target, $targetPort, $expected]) {
    $normalized = monitor_normalize_host($target);
    $localIp = monitor_valid_host($normalized) ? monitor_resolve($normalized) : null;
    $public = ($localIp !== null && filter_var($normalized, FILTER_VALIDATE_IP) === false && strcasecmp($normalized, 'localhost') !== 0)
        ? monitor_public_dns($normalized)
        : null;

    $start = microtime(true);
    $result = monitor_check($target, $targetPort);
    $ms = (int)round((microtime(true) - $start) * 1000);

    echo str_pad($target . ':' . $targetPort, 45)
        . ($result['online'] ? 'ONLINE ' : 'OFFLINE')
        . '  ' . str_pad($result['method'], 18)
        . '  ' . $ms . 'ms  ' . $result['errstr']
        . '  [' . $expected . ']' . "\n";
    echo '    host=' . var_export($normalized, true)
        . '  local_ip=' . var_export($localIp, true)
        . '  public_dns=' . var_export($public, true) . "\n";
}
