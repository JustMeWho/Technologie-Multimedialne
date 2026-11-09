<?php
declare(strict_types=1);

/* ============================================================================
   Sprawdzanie dostepnosci uslug sieciowych dla zadania 4.

   InfinityFree proxuje polaczenia wychodzace, dlatego samo fsockopen() potrafi
   zwrocic sukces nawet dla nieistniejacej domeny. Tutaj najpierw sprawdzamy
   DNS, a potem wymagamy rzeczywistej odpowiedzi protokolu (HTTP, banner itp.).
   ============================================================================ */

function monitor_normalize_host(string $host): string
{
    $host = trim($host);
    $host = preg_replace('~^[a-z][a-z0-9+.-]*://~i', '', $host) ?? $host;
    $parts = preg_split('~[/?#]~', $host, 2);
    $host = $parts[0] ?? $host;
    return trim($host, " \t\n\r\0\x0B.");
}

function monitor_valid_host(string $host): bool
{
    if ($host === '' || strlen($host) > 253) {
        return false;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return true;
    }
    if (strcasecmp($host, 'localhost') === 0) {
        return true;
    }
    return (bool)preg_match(
        '/^(?=.{1,253}$)(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))*\.?$/',
        $host
    );
}

function monitor_resolve(string $host): ?string
{
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return $host;
    }

    $ip = @gethostbyname($host);
    if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    $ips = @gethostbynamel($host);
    if (is_array($ips) && isset($ips[0]) && filter_var($ips[0], FILTER_VALIDATE_IP)) {
        return $ips[0];
    }

    $records = @dns_get_record($host, DNS_A | DNS_AAAA);
    if (is_array($records)) {
        foreach ($records as $record) {
            if (!empty($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP)) {
                return $record['ip'];
            }
            if (!empty($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP)) {
                return $record['ipv6'];
            }
        }
    }

    return null;
}

/**
 * Sprawdzenie istnienia domeny przez publiczny resolver DNS-over-HTTPS.
 * Lokalny resolver hostingu bywa bezuzyteczny (wildcard/proxy), dlatego
 * dodatkowo pytamy Cloudflare/Google. null = nie udalo sie ustalic.
 */
function monitor_public_dns(string $host): ?bool
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $key = strtolower($host);
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['z4_dns_cache'][$key])) {
        $cached = $_SESSION['z4_dns_cache'][$key];
        $ttl = $cached['ok'] === null ? 60 : 300;
        if (time() - (int)$cached['ts'] < $ttl) {
            return $cached['ok'];
        }
    }

    $urls = [
        'https://cloudflare-dns.com/dns-query?name=' . rawurlencode($host) . '&type=A',
        'https://dns.google/resolve?name=' . rawurlencode($host) . '&type=A',
    ];

    $result = null;
    foreach ($urls as $url) {
        $ch = curl_init($url);
        if ($ch === false) {
            continue;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => ['Accept: application/dns-json'],
            CURLOPT_USERAGENT => 'Z4-Monitor/1.0',
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false || $code < 200 || $code >= 300) {
            continue;
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data) || !isset($data['Status'])) {
            continue;
        }

        $status = (int)$data['Status'];
        if ($status === 3) {
            $result = false;
            break;
        }
        if ($status === 0) {
            $result = true;
            break;
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['z4_dns_cache'][$key] = ['ok' => $result, 'ts' => time()];
    }

    return $result;
}

function monitor_is_web_port(int $port): bool
{
    return in_array($port, [80, 443, 8000, 8080, 8443], true);
}

function monitor_is_tls_port(int $port): bool
{
    return in_array($port, [443, 465, 8443, 993, 995], true);
}

function monitor_request(int $port, string $host): string
{
    if (monitor_is_web_port($port)) {
        return "GET / HTTP/1.0\r\n"
             . "Host: {$host}\r\n"
             . "User-Agent: Z4-Monitor/1.0\r\n"
             . "Accept: */*\r\n"
             . "Range: bytes=0-0\r\n"
             . "Connection: close\r\n\r\n";
    }
    if ($port === 6379) {
        return "PING\r\n";
    }
    if ($port === 11211) {
        return "version\r\n";
    }
    if (in_array($port, [25, 465, 587], true)) {
        return "EHLO monitor.local\r\n";
    }
    if ($port === 21) {
        return "NOOP\r\n";
    }
    if ($port === 5432) {
        $params = "user\0postgres\0database\0postgres\0\0";
        return pack('N', 8 + strlen($params)) . pack('N', 196608) . $params;
    }
    return "\r\n";
}

function monitor_answer_ok(int $port, string $response): bool
{
    if ($response === '') {
        return false;
    }

    $trim = ltrim($response);

    if (monitor_is_web_port($port)) {
        return (bool)preg_match('~^HTTP/\d(?:\.\d)?\s+\d{3}~i', $trim);
    }
    if (in_array($port, [25, 465, 587], true)) {
        return strncmp($trim, '220', 3) === 0;
    }
    if (in_array($port, [110, 995], true)) {
        return strncmp($trim, '+OK', 3) === 0;
    }
    if (in_array($port, [143, 993], true)) {
        return strncasecmp($trim, '* OK', 4) === 0;
    }
    if ($port === 21) {
        return strncmp($trim, '220', 3) === 0;
    }
    if ($port === 22) {
        return strncmp($trim, 'SSH-', 4) === 0;
    }
    if ($port === 3306) {
        return strlen($response) >= 5 && ord($response[4]) === 10;
    }
    if ($port === 6379) {
        return strpos($response, '+PONG') !== false
            || strpos($response, '-NOAUTH') !== false
            || strpos($response, '-ERR') !== false;
    }
    if ($port === 11211) {
        return stripos($response, 'VERSION') !== false;
    }
    if ($port === 5432) {
        return strlen($response) >= 5 && ($response[0] === 'R' || $response[0] === 'E');
    }

    return trim($response) !== '';
}

function monitor_read(int $port, $socket): string
{
    stream_set_timeout($socket, 1);

    $response = '';
    $deadline = microtime(true) + 2.5;

    while (strlen($response) < 2048 && microtime(true) < $deadline && !feof($socket)) {
        $chunk = @fread($socket, 512);
        if ($chunk === false) {
            break;
        }
        if ($chunk === '') {
            $meta = stream_get_meta_data($socket);
            if (!empty($meta['timed_out']) || !empty($meta['eof'])) {
                break;
            }
            usleep(50000);
            continue;
        }

        $response .= $chunk;
        if (monitor_answer_ok($port, $response)) {
            break;
        }
    }

    return $response;
}

/**
 * @return array{online: bool, errno: int, errstr: string, method: string}
 */
function monitor_check(string $host, int $port): array
{
    $host = monitor_normalize_host($host);

    if (!monitor_valid_host($host)) {
        return ['online' => false, 'errno' => 0, 'errstr' => 'Nieprawidlowa nazwa hosta', 'method' => 'walidacja'];
    }
    if ($port < 1 || $port > 65535) {
        return ['online' => false, 'errno' => 0, 'errstr' => 'Nieprawidlowy port', 'method' => 'walidacja'];
    }

    $ip = monitor_resolve($host);
    if ($ip === null) {
        return ['online' => false, 'errno' => 0, 'errstr' => 'Nie mozna rozwiazac nazwy hosta (DNS)', 'method' => 'DNS'];
    }

    $is_ip = filter_var($host, FILTER_VALIDATE_IP) !== false;
    if (!$is_ip && strcasecmp($host, 'localhost') !== 0) {
        $exists = monitor_public_dns($host);
        if ($exists === false) {
            return ['online' => false, 'errno' => 0, 'errstr' => 'Domena nie istnieje (NXDOMAIN)', 'method' => 'DNS publiczny'];
        }
    }

    $scheme = monitor_is_tls_port($port) ? 'ssl' : 'tcp';
    $context = null;
    if ($scheme === 'ssl') {
        if (!extension_loaded('openssl')) {
            return ['online' => false, 'errno' => 0, 'errstr' => 'Brak rozszerzenia OpenSSL', 'method' => 'SSL'];
        }
        $context = stream_context_create([
            'ssl' => [
                'SNI_enabled' => true,
                'peer_name' => $host,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);
    }

    $errno = 0;
    $errstr = '';
    $started = microtime(true);
    $socket = @stream_socket_client(
        "{$scheme}://{$host}:{$port}",
        $errno,
        $errstr,
        4,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        return [
            'online' => false,
            'errno' => (int)$errno,
            'errstr' => $errstr !== '' ? $errstr : 'Brak polaczenia',
            'method' => $scheme . ' connect',
        ];
    }

    stream_set_blocking($socket, true);

    $request = monitor_request($port, $host);
    if ($request !== '') {
        @fwrite($socket, $request);
    }

    $response = monitor_read($port, $socket);
    @fclose($socket);

    $method = $scheme . '/' . (int)round((microtime(true) - $started) * 1000) . 'ms';

    if (!monitor_answer_ok($port, $response)) {
        $errstr = $response === '' ? 'Brak odpowiedzi uslugi' : 'Nieoczekiwana odpowiedz uslugi';
        if (trim($response) !== '') {
            $preview = preg_replace('~[^\x20-\x7E]~', '.', substr($response, 0, 40));
            $errstr .= ' (' . $preview . ')';
        }
        return ['online' => false, 'errno' => 0, 'errstr' => $errstr, 'method' => $method];
    }

    return ['online' => true, 'errno' => 0, 'errstr' => '', 'method' => $method];
}
