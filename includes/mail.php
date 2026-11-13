<?php

declare(strict_types=1);

function mail_address(string $value): string
{
    $value = header_safe($value);
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
}

function encoded_header(string $value): string
{
    $value = header_safe($value);
    if ($value === '') {
        return '';
    }
    return mb_encode_mimeheader($value, 'UTF-8');
}

function mail_rate_limited(): bool
{
    $times = $_SESSION['mail_times'] ?? [];
    if (!is_array($times)) {
        $times = [];
    }
    $times = array_values(array_filter($times, static function ($time): bool {
        return is_int($time) && $time > time() - 3600;
    }));
    $_SESSION['mail_times'] = $times;
    return count($times) >= 5;
}

function mark_mail_sent(): void
{
    $_SESSION['mail_times'][] = time();
}

function send_inquiry(array $site, array $input, ?array $oglas): bool
{
    $to = mail_address((string) (config()['mail_to'] ?? ''));
    if ($to === '') {
        $to = mail_address((string) ($site['email'] ?? ''));
    }
    $from = mail_address((string) (config()['mail_from'] ?? ''));
    if ($from === '') {
        $from = mail_address((string) ($site['email'] ?? ''));
    }
    if ($to === '' || $from === '') {
        return false;
    }

    $naslov = $oglas ? (string) ($oglas['naslov'] ?? '') : '';
    $subject = 'Website inquiry' . ($naslov !== '' ? ': ' . clip($naslov, 80) : '');
    $lines = [
        'Name: ' . $input['ime'],
        'Email: ' . $input['email'],
        'Phone: ' . ($input['telefon'] !== '' ? $input['telefon'] : '—'),
    ];
    if ($oglas) {
        $lines[] = 'Listing: ' . $naslov . ' (' . $oglas['id'] . ')';
        $base = rtrim((string) (config()['base_url'] ?? ''), '/');
        if ($base !== '') {
            $lines[] = 'Link: ' . $base . url('nekretnina.php?id=' . rawurlencode((string) $oglas['id']));
        }
    }
    $lines[] = '';
    $lines[] = $input['poruka'];
    $body = implode("\r\n", $lines);

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . encoded_header((string) ($site['naziv'] ?? 'Website')) . ' <' . $from . '>',
        'Reply-To: ' . encoded_header($input['ime']) . ' <' . $input['email'] . '>',
    ];
    $headerString = implode("\r\n", $headers);
    $encodedSubject = encoded_header($subject);
    if (PHP_OS_FAMILY === 'Windows') {
        return @mail($to, $encodedSubject, $body, $headerString);
    }
    return @mail($to, $encodedSubject, $body, $headerString, '-f' . $from);
}
