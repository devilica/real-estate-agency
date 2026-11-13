<?php

declare(strict_types=1);

function site_defaults(): array
{
    return [
        'naziv' => 'ELITE ESTATE Real Estate Agency',
        'slogan' => 'Find a space',
        'slogan_akcent' => 'that fits.',
        'uvod' => 'Browse apartments, houses, land and commercial spaces in Banja Luka and the surrounding area. Whether you are buying or renting, we will help you find the right property.',
        'o_nama' => 'Your property. Our commitment.',
        'telefon' => '+1 (202) 555-0147',
        'email' => 'info@eliteestate.example',
        'adresa' => 'Kralja Petra I Karađorđevića 85, Banja Luka',
        'radno_vrijeme' => 'Mon–Fri 09–17',
        'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Kralja%20Petra%20I%20Kara%C4%91or%C4%91evi%C4%87a%2085%2C%20Banja%20Luka',
    ];
}

function read_json(string $file): array
{
    if (!is_file($file)) {
        throw new RuntimeException('Missing ' . basename($file) . '.');
    }
    $raw = file_get_contents($file);
    if ($raw === false) {
        throw new RuntimeException('Cannot read ' . basename($file) . '.');
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new RuntimeException(basename($file) . ' is damaged. If a .bak copy exists in the same folder, restore it.');
    }
    if (!is_array($data)) {
        throw new RuntimeException(basename($file) . ' is damaged.');
    }
    return $data;
}

function write_json(string $file, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('The data could not be saved.');
    }
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the data folder.');
    }
    if (is_file($file)) {
        @copy($file, $file . '.bak');
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Cannot write the data. Check the permissions of the data folder.');
    }
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException('Cannot replace the data.');
    }
}

function site(bool $reload = false): array
{
    static $cache = null;
    if ($reload) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }
    $defaults = site_defaults();
    $data = read_json(ROOT . '/data/stranice.json');
    if (array_is_list($data)) {
        throw new RuntimeException('stranice.json is damaged.');
    }
    $clean = [];
    foreach ($defaults as $key => $value) {
        $clean[$key] = array_key_exists($key, $data) ? trim((string) $data[$key]) : $value;
    }
    $cache = $clean;
    return $cache;
}

function save_site(array $input): void
{
    $clean = [];
    foreach (site_defaults() as $key => $value) {
        $clean[$key] = trim((string) ($input[$key] ?? $value));
    }
    write_json(ROOT . '/data/stranice.json', $clean);
    site(true);
}

function listings(bool $reload = false): array
{
    static $cache = null;
    if ($reload) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }
    $data = read_json(ROOT . '/data/nekretnine.json');
    if (!array_is_list($data)) {
        throw new RuntimeException('nekretnine.json must be a list of listings.');
    }
    $cache = $data;
    if (needs_order($data)) {
        $ordered = renumber_listings($data);
        try {
            write_json(ROOT . '/data/nekretnine.json', $ordered);
        } catch (RuntimeException) {
        }
        $cache = $ordered;
    }
    return $cache;
}

function save_listings(array $items): void
{
    write_json(ROOT . '/data/nekretnine.json', renumber_listings(array_values($items)));
    listings(true);
}

function needs_order(array $items): bool
{
    foreach ($items as $row) {
        if (is_array($row) && !is_int($row['redoslijed'] ?? null)) {
            return true;
        }
    }
    return false;
}

function sort_listings(array $items): array
{
    usort($items, static function (array $a, array $b): int {
        $ra = is_int($a['redoslijed'] ?? null) ? $a['redoslijed'] : PHP_INT_MAX;
        $rb = is_int($b['redoslijed'] ?? null) ? $b['redoslijed'] : PHP_INT_MAX;
        if ($ra !== $rb) {
            return $ra <=> $rb;
        }
        $featured = ((int) !empty($b['istaknuto'])) <=> ((int) !empty($a['istaknuto']));
        if ($featured !== 0) {
            return $featured;
        }
        return strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
    });
    return $items;
}

function renumber_listings(array $items): array
{
    $rows = [];
    $other = [];
    foreach ($items as $row) {
        if (is_array($row)) {
            $rows[] = $row;
        } else {
            $other[] = $row;
        }
    }
    $rows = sort_listings($rows);
    foreach ($rows as $i => $row) {
        $rows[$i]['redoslijed'] = $i + 1;
    }
    return array_merge($rows, $other);
}

/**
 * Saves a new order. Listings that are not in the list keep their place, after the ones listed.
 *
 * @param list<mixed> $ids
 */
function reorder_listings(array $ids): bool
{
    $all = sort_listings(array_values(array_filter(listings(), 'is_array')));
    $byId = [];
    foreach ($all as $row) {
        $id = (string) ($row['id'] ?? '');
        if ($id !== '') {
            $byId[$id] = $row;
        }
    }
    $ordered = [];
    foreach ($ids as $id) {
        if (!is_string($id) || !isset($byId[$id]) || isset($ordered[$id])) {
            continue;
        }
        $ordered[$id] = $byId[$id];
    }
    if ($ordered === []) {
        return false;
    }
    foreach ($byId as $id => $row) {
        if (!isset($ordered[$id])) {
            $ordered[$id] = $row;
        }
    }
    $next = array_values($ordered);
    foreach ($next as $i => $row) {
        $next[$i]['redoslijed'] = $i + 1;
    }
    save_listings($next);
    return true;
}

function move_listing(string $id, int $step): bool
{
    $ids = array_map(static fn (array $row): string => (string) ($row['id'] ?? ''), sort_listings(array_values(array_filter(listings(), 'is_array'))));
    $pos = array_search($id, $ids, true);
    if ($pos === false) {
        return false;
    }
    $target = $pos + $step;
    if ($target < 0 || $target >= count($ids)) {
        return false;
    }
    [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];
    return reorder_listings($ids);
}

function usable_listing(mixed $oglas): bool
{
    return is_array($oglas)
        && isset($oglas['id'], $oglas['naslov'])
        && valid_id((string) $oglas['id']);
}

function all_listings(): array
{
    $items = array_values(array_filter(listings(), 'usable_listing'));
    return sort_listings($items);
}

function public_listings(array $filter = []): array
{
    $transakcija = (string) ($filter['transakcija'] ?? '');
    $tip = (string) ($filter['tip'] ?? '');
    $grad = (string) ($filter['grad'] ?? '');
    $items = array_values(array_filter(all_listings(), static function (array $oglas) use ($transakcija, $tip, $grad): bool {
        if (!empty($oglas['sakriveno'])) {
            return false;
        }
        if ($transakcija !== '' && (string) ($oglas['transakcija'] ?? '') !== $transakcija) {
            return false;
        }
        if ($tip !== '' && (string) ($oglas['tip'] ?? '') !== $tip) {
            return false;
        }
        if ($grad !== '' && mb_strtolower((string) ($oglas['grad'] ?? '')) !== mb_strtolower($grad)) {
            return false;
        }
        return true;
    }));
    return $items;
}

const EUR_U_KM = 1.95583;

function cijena_u_km(array $oglas): ?float
{
    if (!isset($oglas['cijena']) || $oglas['cijena'] === null || $oglas['cijena'] === '') {
        return null;
    }
    $iznos = (float) $oglas['cijena'];
    return (($oglas['valuta'] ?? 'BAM') === 'EUR') ? $iznos * EUR_U_KM : $iznos;
}

function sort_by_price(array $items, bool $desc): array
{
    $indexed = [];
    foreach (array_values($items) as $i => $oglas) {
        $indexed[] = [$i, cijena_u_km($oglas), $oglas];
    }
    usort($indexed, static function (array $a, array $b) use ($desc): int {
        if ($a[1] === null || $b[1] === null) {
            return ($a[1] === null) <=> ($b[1] === null) ?: $a[0] <=> $b[0];
        }
        $cmp = $desc ? $b[1] <=> $a[1] : $a[1] <=> $b[1];
        return $cmp ?: $a[0] <=> $b[0];
    });
    return array_column($indexed, 2);
}

function find_listing(string $id, bool $includeHidden = false): ?array
{
    if (!valid_id($id)) {
        return null;
    }
    foreach (all_listings() as $oglas) {
        if ((string) $oglas['id'] !== $id) {
            continue;
        }
        if (!$includeHidden && !empty($oglas['sakriveno'])) {
            return null;
        }
        return $oglas;
    }
    return null;
}

function gradovi(): array
{
    $names = [];
    foreach (public_listings() as $oglas) {
        $name = trim((string) ($oglas['grad'] ?? ''));
        if ($name !== '') {
            $names[$name] = $name;
        }
    }
    natcasesort($names);
    return array_values($names);
}

function listing_images(array $oglas): array
{
    $id = (string) ($oglas['id'] ?? '');
    $out = [];
    foreach ($oglas['slike'] ?? [] as $rel) {
        if (is_string($rel) && owned_image($id, $rel)) {
            $out[] = $rel;
        }
    }
    return $out;
}

function has_example_listings(): bool
{
    foreach (all_listings() as $oglas) {
        if (str_starts_with((string) $oglas['id'], 'primjer-')) {
            return true;
        }
    }
    return false;
}

function unique_listing_id(string $naslov): string
{
    $base = slugify($naslov);
    if ($base === '') {
        $base = 'oglas';
    }
    $base = mb_substr($base, 0, 48);
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'oglas';
    }
    $taken = [];
    foreach (listings() as $oglas) {
        if (isset($oglas['id'])) {
            $taken[(string) $oglas['id']] = true;
        }
    }
    do {
        $id = $base . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
    } while (isset($taken[$id]) || !valid_id($id));
    return $id;
}
