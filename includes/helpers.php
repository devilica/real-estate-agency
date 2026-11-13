<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function query_string(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function url(string $path = ''): string
{
    $base = rtrim((string) (config()['base_path'] ?? ''), '/');
    if ($path === '' || $path === '/') {
        return ($base === '' ? '' : $base) . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type === 'err' ? 'err' : 'ok',
    ];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_ok(): bool
{
    $sent = (string) ($_POST['csrf'] ?? '');
    $real = (string) ($_SESSION['csrf'] ?? '');
    return $real !== '' && hash_equals($real, $sent);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function checked(bool $on): string
{
    return $on ? ' checked' : '';
}

function selected(bool $on): string
{
    return $on ? ' selected' : '';
}

function clip(string $text, int $max): string
{
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}

function header_safe(string $value): string
{
    return trim(str_replace(["\r", "\n", '"'], '', $value));
}

function plural_bs(int $n, string $one, string $few, string $many): string
{
    $n = abs($n);
    if ($n % 100 >= 11 && $n % 100 <= 14) {
        return $many;
    }
    $d = $n % 10;
    if ($d === 1) {
        return $one;
    }
    if ($d >= 2 && $d <= 4) {
        return $few;
    }
    return $many;
}

function slugify(string $text): string
{
    $text = strtr($text, [
        'š' => 's', 'đ' => 'dj', 'č' => 'c', 'ć' => 'c', 'ž' => 'z',
        'Š' => 's', 'Đ' => 'dj', 'Č' => 'c', 'Ć' => 'c', 'Ž' => 'z',
    ]);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function valid_id(string $id): bool
{
    return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id);
}

function tip_opcije(): array
{
    return [
        'stan' => 'Apartment',
        'kuca' => 'House',
        'zemljiste' => 'Land',
        'poslovni' => 'Commercial space',
        'poslovni-objekat' => 'Commercial building',
        'garaza' => 'Garage / Parking',
        'apartman' => 'Holiday apartment',
        'vikend' => 'Holiday home',
        'ostalo' => 'Other',
    ];
}

function podtip_opcije(): array
{
    return [
        'stan' => [
            'garsonjera' => 'Studio',
            'jednosoban' => 'One-bedroom',
            'dvosoban' => 'Two-bedroom',
            'trosoban' => 'Three-bedroom',
            'penthouse' => 'Penthouse',
        ],
        'kuca' => [
            'porodicna' => 'Family house',
            'vikendica' => 'Cottage',
            'dvojna' => 'Duplex',
        ],
        'zemljiste' => [
            'gradjevinsko' => 'Building land',
            'poljoprivredno' => 'Agricultural',
            'sumsko' => 'Forest',
        ],
        'poslovni' => [
            'lokal' => 'Shop',
            'kancelarija' => 'Office',
            'ordinacija' => 'Medical office',
        ],
        'poslovni-objekat' => [
            'zgrada' => 'Building',
            'hala' => 'Hall',
            'skladiste' => 'Warehouse',
        ],
        'garaza' => [
            'garaza' => 'Garage',
            'garazno-mjesto' => 'Garage space',
            'parking' => 'Parking space',
        ],
        'apartman' => [
            'turisticki' => 'Holiday apartment',
            'jedinica' => 'Apartment unit',
        ],
        'vikend' => [
            'vikend-kuca' => 'Weekend house',
            'planinska' => 'Mountain house',
        ],
        'ostalo' => [
            'ostalo' => 'Other property types',
        ],
    ];
}

function tip_label(string $tip): string
{
    return tip_opcije()[$tip] ?? $tip;
}

function podtip_label(string $tip, string $podtip): string
{
    return podtip_opcije()[$tip][$podtip] ?? '';
}

function tip_prikaz(array $oglas): string
{
    $tip = (string) ($oglas['tip'] ?? '');
    $label = tip_label($tip);
    $podtip = podtip_label($tip, (string) ($oglas['podtip'] ?? ''));
    return $podtip !== '' ? $label . ' · ' . $podtip : $label;
}

function transakcija_label(string $tip): string
{
    return $tip === 'najam' ? 'Rent' : 'Sale';
}

function status_label(array $oglas): string
{
    $status = (string) ($oglas['status'] ?? 'aktivno');
    if ($status === 'rezervisano') {
        return 'Reserved';
    }
    if ($status === 'prodato') {
        return (($oglas['transakcija'] ?? '') === 'najam') ? 'Rented' : 'Sold';
    }
    return '';
}

function format_cijena(array $oglas): string
{
    if (!isset($oglas['cijena']) || $oglas['cijena'] === null || $oglas['cijena'] === '') {
        return 'On request';
    }
    $iznos = number_format((float) $oglas['cijena'], 0, '.', ',');
    $valuta = (($oglas['valuta'] ?? 'BAM') === 'EUR') ? 'EUR' : 'KM';
    return $iznos . ' ' . $valuta;
}

function format_m2(mixed $n): string
{
    if ($n === null || $n === '') {
        return '';
    }
    $n = (float) $n;
    $text = abs($n - round($n)) < 0.05
        ? number_format($n, 0, '.', ',')
        : number_format($n, 1, '.', ',');
    return $text . ' m²';
}

function format_sobe(mixed $n): string
{
    if ($n === null || $n === '') {
        return '';
    }
    $n = (int) $n;
    return $n . ' ' . ($n === 1 ? 'room' : 'rooms');
}

function tip_opcije_en(): array
{
    return [
        'stan' => 'Apartment',
        'kuca' => 'House',
        'zemljiste' => 'Land',
        'poslovni' => 'Commercial space',
        'poslovni-objekat' => 'Commercial building',
        'garaza' => 'Garage / Parking',
        'apartman' => 'Holiday apartment',
        'vikend' => 'Holiday home',
        'ostalo' => 'Other',
    ];
}

function podtip_opcije_en(): array
{
    return [
        'stan' => [
            'garsonjera' => 'Studio',
            'jednosoban' => 'One-bedroom',
            'dvosoban' => 'Two-bedroom',
            'trosoban' => 'Three-bedroom',
            'penthouse' => 'Penthouse',
        ],
        'kuca' => [
            'porodicna' => 'Family house',
            'vikendica' => 'Cottage',
            'dvojna' => 'Duplex',
        ],
        'zemljiste' => [
            'gradjevinsko' => 'Building land',
            'poljoprivredno' => 'Agricultural',
            'sumsko' => 'Forest',
        ],
        'poslovni' => [
            'lokal' => 'Shop',
            'kancelarija' => 'Office',
            'ordinacija' => 'Medical office',
        ],
        'poslovni-objekat' => [
            'zgrada' => 'Building',
            'hala' => 'Hall',
            'skladiste' => 'Warehouse',
        ],
        'garaza' => [
            'garaza' => 'Garage',
            'garazno-mjesto' => 'Garage space',
            'parking' => 'Parking space',
        ],
        'apartman' => [
            'turisticki' => 'Holiday apartment',
            'jedinica' => 'Apartment unit',
        ],
        'vikend' => [
            'vikend-kuca' => 'Weekend house',
            'planinska' => 'Mountain house',
        ],
        'ostalo' => [
            'ostalo' => 'Other property types',
        ],
    ];
}

function tip_prikaz_en(array $oglas): string
{
    $tip = (string) ($oglas['tip'] ?? '');
    $label = tip_opcije_en()[$tip] ?? tip_label($tip);
    $podtip = podtip_opcije_en()[$tip][(string) ($oglas['podtip'] ?? '')] ?? '';
    return $podtip !== '' ? $label . ' · ' . $podtip : $label;
}

function transakcija_label_en(string $tip): string
{
    return $tip === 'najam' ? 'Rent' : 'Sale';
}

function status_label_en(array $oglas): string
{
    $status = (string) ($oglas['status'] ?? 'aktivno');
    if ($status === 'rezervisano') {
        return 'Reserved';
    }
    if ($status === 'prodato') {
        return (($oglas['transakcija'] ?? '') === 'najam') ? 'Rented' : 'Sold';
    }
    return '';
}

function format_cijena_en(array $oglas): string
{
    if (!isset($oglas['cijena']) || $oglas['cijena'] === null || $oglas['cijena'] === '') {
        return 'On request';
    }
    $iznos = number_format((float) $oglas['cijena'], 0, '.', ',');
    $valuta = (($oglas['valuta'] ?? 'BAM') === 'EUR') ? 'EUR' : 'KM';
    return $iznos . ' ' . $valuta;
}

function format_m2_en(mixed $n): string
{
    if ($n === null || $n === '') {
        return '';
    }
    $n = (float) $n;
    $text = abs($n - round($n)) < 0.05
        ? number_format($n, 0, '.', ',')
        : number_format($n, 1, '.', ',');
    return $text . ' m²';
}

function format_sobe_en(mixed $n): string
{
    if ($n === null || $n === '') {
        return '';
    }
    $n = (int) $n;
    return $n . ' ' . ($n === 1 ? 'room' : 'rooms');
}

function plural_en(int $n, string $one, string $many): string
{
    return abs($n) === 1 ? $one : $many;
}

function listing_bits(array $oglas): string
{
    $bits = [];
    $m2 = format_m2($oglas['povrsina'] ?? null);
    $sobe = format_sobe($oglas['sobe'] ?? null);
    if ($m2 !== '') {
        $bits[] = $m2;
    }
    if ($sobe !== '') {
        $bits[] = $sobe;
    }
    return implode(' · ', $bits);
}

function paragraphs(string $text): string
{
    $blocks = preg_split("/\n\s*\n/", trim($text)) ?: [];
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $html .= '<p>' . nl2br(e($block), false) . '</p>';
    }
    return $html;
}

function tel_href(string $phone): string
{
    $n = preg_replace('/[^0-9+]/', '', $phone) ?? '';
    return 'tel:' . $n;
}

function maps_href(array $site): string
{
    $url = trim((string) ($site['maps_url'] ?? ''));
    if ($url !== '' && preg_match('#^https://#i', $url)) {
        return $url;
    }
    $q = trim((string) ($site['adresa'] ?? ''));
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
}

function query_url(string $path, array $params): string
{
    $clean = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === 0) {
            continue;
        }
        $clean[$key] = $value;
    }
    $qs = http_build_query($clean);
    return url($path . ($qs !== '' ? '?' . $qs : ''));
}

function logo_img(): string
{
    $alt = site()['naziv'] !== '' ? site()['naziv'] : 'ELITE ESTATE Real Estate Agency';
    return '<img class="brand-logo" src="' . e(url('assets/logo-light.png')) . '" alt="' . e($alt) . '" width="840" height="272">';
}

function icon(string $name): string
{
    $paths = [
        'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'area' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
        'bed' => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 15h18M7 10V7h4v3"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'chevron-up' => '<path d="m6 15 6-6 6 6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'grip' => '<circle cx="9" cy="6" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="18" r="1"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
        'home' => '<path d="M4 11 12 4l8 7v9H4Z"/><path d="M10 20v-6h4v6"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8Z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M14 9l2 2"/>',
        'pencil' => '<path d="M4 20h4l11-11-4-4L4 16Z"/><path d="m13 7 4 4"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18M10.5 6.2A10 10 0 0 1 12 6c6 0 10 6 10 6a18 18 0 0 1-3.2 3.8M6.1 6.1C3.5 7.8 2 12 2 12a18 18 0 0 0 6.4 5.2"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'trash' => '<path d="M4 7h16M9 7V5h6v2M7 7l1 13h8l1-13"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    ];
    $body = $paths[$name] ?? '';
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

function mark_svg(): string
{
    return '<svg class="mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M5 14.5 16 5l11 9.5V27H5Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M13 27v-8h6v8" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>';
}

function accent_title(string $plain, string $accent): string
{
    $plain = trim($plain);
    $accent = trim($accent);
    if ($accent === '') {
        return e($plain);
    }
    return e($plain) . ' <span class="accent">' . e($accent) . '</span>';
}
