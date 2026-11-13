<?php

declare(strict_types=1);

function blank_listing_form(): array
{
    return [
        'id' => '',
        'naslov' => '',
        'tip' => 'stan',
        'podtip' => '',
        'transakcija' => 'prodaja',
        'cijena' => '',
        'valuta' => 'BAM',
        'grad' => '',
        'lokacija' => '',
        'povrsina' => '',
        'sobe' => '',
        'opis' => '',
        'status' => 'aktivno',
        'istaknuto' => false,
        'sakriveno' => false,
        'slike' => [],
    ];
}

function decimal_input(mixed $n): string
{
    if ($n === null || $n === '') {
        return '';
    }
    $n = (float) $n;
    if (abs($n - round($n)) < 0.05) {
        return (string) (int) round($n);
    }
    return rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
}

function listing_to_form(array $oglas): array
{
    $form = blank_listing_form();
    $form['id'] = (string) $oglas['id'];
    $form['naslov'] = (string) ($oglas['naslov'] ?? '');
    $tip = (string) ($oglas['tip'] ?? 'stan');
    $form['tip'] = isset(tip_opcije()[$tip]) ? $tip : 'stan';
    $podtip = (string) ($oglas['podtip'] ?? '');
    $form['podtip'] = isset(podtip_opcije()[$form['tip']][$podtip]) ? $podtip : '';
    $form['transakcija'] = (($oglas['transakcija'] ?? '') === 'najam') ? 'najam' : 'prodaja';
    $form['cijena'] = (isset($oglas['cijena']) && $oglas['cijena'] !== null && $oglas['cijena'] !== '')
        ? (string) (int) $oglas['cijena']
        : '';
    $form['valuta'] = (($oglas['valuta'] ?? '') === 'EUR') ? 'EUR' : 'BAM';
    $form['grad'] = (string) ($oglas['grad'] ?? '');
    $form['lokacija'] = (string) ($oglas['lokacija'] ?? '');
    $form['povrsina'] = decimal_input($oglas['povrsina'] ?? null);
    $form['sobe'] = (isset($oglas['sobe']) && $oglas['sobe'] !== null && $oglas['sobe'] !== '')
        ? (string) (int) $oglas['sobe']
        : '';
    $form['opis'] = (string) ($oglas['opis'] ?? '');
    $status = (string) ($oglas['status'] ?? 'aktivno');
    $form['status'] = in_array($status, ['aktivno', 'rezervisano', 'prodato'], true) ? $status : 'aktivno';
    $form['istaknuto'] = !empty($oglas['istaknuto']);
    $form['sakriveno'] = !empty($oglas['sakriveno']);
    $form['slike'] = listing_images($oglas);
    return $form;
}

function form_from_post(): array
{
    $form = blank_listing_form();
    $form['id'] = post_string('id');
    $form['naslov'] = post_string('naslov');
    $form['tip'] = post_string('tip');
    $form['podtip'] = post_string('podtip');
    $form['transakcija'] = post_string('transakcija');
    $form['cijena'] = post_string('cijena');
    $form['valuta'] = post_string('valuta');
    $form['grad'] = post_string('grad');
    $form['lokacija'] = post_string('lokacija');
    $form['povrsina'] = post_string('povrsina');
    $form['sobe'] = post_string('sobe');
    $opis = post_string('opis');
    $opis = str_replace(["\r\n", "\r"], "\n", $opis);
    $form['opis'] = $opis;
    $form['status'] = post_string('status');
    $form['istaknuto'] = isset($_POST['istaknuto']);
    $form['sakriveno'] = isset($_POST['sakriveno']);
    return $form;
}

function parse_cijena(string $raw): ?int
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $raw = str_replace([' ', '.'], '', $raw);
    $raw = str_replace(',', '.', $raw);
    if (!preg_match('/^\d+(\.\d+)?$/', $raw)) {
        throw new InvalidArgumentException('The price must be a number, or left empty for “On request”.');
    }
    $value = (int) round((float) $raw);
    if ($value < 0 || $value > 999999999) {
        throw new InvalidArgumentException('The price is outside the allowed range.');
    }
    return $value;
}

function parse_povrsina(string $raw): ?float
{
    $raw = trim(str_replace(' ', '', $raw));
    if ($raw === '') {
        return null;
    }
    if (str_contains($raw, ',')) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
        $raw = str_replace('.', '', $raw);
    }
    if (!is_numeric($raw)) {
        throw new InvalidArgumentException('The area must be a number.');
    }
    $value = round((float) $raw, 1);
    if ($value <= 0 || $value > 10000000) {
        throw new InvalidArgumentException('The area is outside the allowed range.');
    }
    return $value;
}

function parse_sobe(string $raw): ?int
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (!preg_match('/^\d+$/', $raw)) {
        throw new InvalidArgumentException('The number of rooms must be a whole number, or left empty.');
    }
    $value = (int) $raw;
    if ($value < 0 || $value > 50) {
        throw new InvalidArgumentException('The number of rooms is outside the allowed range.');
    }
    return $value;
}

function validate_listing_form(array $form): array
{
    $errors = [];
    if ($form['naslov'] === '' || mb_strlen($form['naslov']) > 140) {
        $errors[] = 'The title is required and can have at most 140 characters.';
    }
    if (!isset(tip_opcije()[$form['tip']])) {
        $errors[] = 'Choose a property type.';
    } elseif ($form['podtip'] !== '' && !isset(podtip_opcije()[$form['tip']][$form['podtip']])) {
        $errors[] = 'The subtype does not belong to the selected type.';
    }
    if (!in_array($form['transakcija'], ['prodaja', 'najam'], true)) {
        $errors[] = 'Choose sale or rent.';
    }
    if (!in_array($form['valuta'], ['EUR', 'BAM'], true)) {
        $errors[] = 'Choose a currency.';
    }
    if (!in_array($form['status'], ['aktivno', 'rezervisano', 'prodato'], true)) {
        $errors[] = 'Choose a status.';
    }
    if ($form['grad'] === '' || mb_strlen($form['grad']) > 80) {
        $errors[] = 'The city is required and can have at most 80 characters.';
    }
    if (mb_strlen($form['lokacija']) > 120) {
        $errors[] = 'The location can have at most 120 characters.';
    }
    if ($form['opis'] === '' || mb_strlen($form['opis']) > 5000) {
        $errors[] = 'The description is required and can have at most 5,000 characters.';
    }
    try {
        parse_cijena($form['cijena']);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
    try {
        parse_povrsina($form['povrsina']);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
    try {
        parse_sobe($form['sobe']);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
    return $errors;
}

function posted_uploads(): array
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && $_POST === []
        && ($_FILES === [] || !isset($_FILES['slike']))
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
    ) {
        throw new RuntimeException('The images are too large for one upload. Add them in smaller groups or reduce their size.');
    }
    return normalize_uploads($_FILES['slike'] ?? []);
}

function incoming_uploads(array $files): array
{
    return array_values(array_filter($files, static function (array $file): bool {
        return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }));
}

/**
 * Saves a listing. Throws RuntimeException if the images cannot be saved.
 * Returns validation errors, or an empty list when the listing is saved.
 *
 * @return list<string>
 */
function save_listing_from_post(?array $existing): array
{
    $form = form_from_post();
    $errors = validate_listing_form($form);
    if ($errors !== []) {
        return $errors;
    }

    $isNew = $existing === null;
    $id = $isNew ? unique_listing_id($form['naslov']) : (string) $existing['id'];
    $current = $isNew ? [] : listing_images($existing);
    $remove = $_POST['obrisi'] ?? [];
    if (!is_array($remove)) {
        $remove = [];
    }
    $remove = array_values(array_filter($remove, 'is_string'));
    $keep = [];
    foreach ($current as $rel) {
        if (!in_array($rel, $remove, true)) {
            $keep[] = $rel;
        }
    }
    $cover = $_POST['naslovna'] ?? '';
    $cover = is_string($cover) ? $cover : '';
    if ($cover !== '' && in_array($cover, $keep, true)) {
        $keep = array_values(array_unique(array_merge([$cover], $keep)));
    }

    try {
        $incoming = incoming_uploads(posted_uploads());
    } catch (RuntimeException $e) {
        return [$e->getMessage()];
    }
    if (count($keep) + count($incoming) > MAX_IMAGES) {
        return ['A listing can have at most 12 images.'];
    }

    $added = [];
    try {
        foreach ($incoming as $file) {
            $added[] = store_upload($file, $id);
        }
    } catch (RuntimeException $e) {
        rollback_uploads($added);
        if ($isNew) {
            delete_listing_files($id);
        }
        return [$e->getMessage()];
    }

    $slike = array_merge($keep, $added);
    $now = date('c');
    $record = [
        'id' => $id,
        'naslov' => $form['naslov'],
        'tip' => $form['tip'],
        'podtip' => $form['podtip'],
        'transakcija' => $form['transakcija'],
        'cijena' => parse_cijena($form['cijena']),
        'valuta' => $form['valuta'],
        'grad' => $form['grad'],
        'lokacija' => $form['lokacija'],
        'povrsina' => parse_povrsina($form['povrsina']),
        'sobe' => parse_sobe($form['sobe']),
        'opis' => $form['opis'],
        'slike' => $slike,
        'status' => $form['status'],
        'istaknuto' => $form['istaknuto'],
        'sakriveno' => $form['sakriveno'],
        'redoslijed' => $isNew ? 0 : (int) ($existing['redoslijed'] ?? 0),
        'created' => $isNew ? $now : (string) ($existing['created'] ?? $now),
        'updated' => $now,
    ];

    $all = listings();
    $replaced = false;
    foreach ($all as $i => $row) {
        if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
            $all[$i] = $record;
            $replaced = true;
            break;
        }
    }
    if (!$replaced) {
        $all[] = $record;
    }

    try {
        save_listings($all);
    } catch (RuntimeException $e) {
        rollback_uploads($added);
        if ($isNew) {
            delete_listing_files($id);
        }
        return [$e->getMessage()];
    }

    cleanup_orphans($id, $slike);
    flash($isNew ? 'The listing has been added.' : 'The listing has been saved.');
    redirect('admin/index.php');
}

function toggle_listing_hidden(string $id): void
{
    if (!valid_id($id)) {
        flash('Listing not found.', 'err');
        return;
    }
    $all = listings();
    foreach ($all as $i => $row) {
        if (!is_array($row) || (string) ($row['id'] ?? '') !== $id) {
            continue;
        }
        $all[$i]['sakriveno'] = empty($row['sakriveno']);
        $all[$i]['updated'] = date('c');
        $hidden = !empty($all[$i]['sakriveno']);
        save_listings($all);
        flash($hidden ? 'The listing is hidden from the site.' : 'The listing is visible again.');
        return;
    }
    flash('Listing not found.', 'err');
}

function delete_listing(string $id): void
{
    if (!valid_id($id)) {
        flash('Listing not found.', 'err');
        return;
    }
    $all = listings();
    $next = [];
    $removed = false;
    foreach ($all as $row) {
        if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
            $removed = true;
            continue;
        }
        $next[] = $row;
    }
    if (!$removed) {
        flash('Listing not found.', 'err');
        return;
    }
    save_listings($next);
    delete_listing_files($id);
    flash('The listing has been deleted.');
}
