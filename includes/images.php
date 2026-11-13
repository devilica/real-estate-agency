<?php

declare(strict_types=1);

const MAX_IMAGES = 12;
const MAX_IMAGE_BYTES = 8 * 1024 * 1024;
const MAX_IMAGE_EDGE = 1600;
const MAX_IMAGE_PIXELS = 24000000;

function uploads_root(): string
{
    return ROOT . '/uploads';
}

function listing_dir(string $id): string
{
    if (!valid_id($id)) {
        throw new InvalidArgumentException('Invalid listing.');
    }
    return uploads_root() . '/' . $id;
}

function path_inside_uploads(string $path): bool
{
    $root = realpath(uploads_root());
    $real = realpath($path);
    if ($root === false || $real === false) {
        return false;
    }
    return $real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR);
}

function ensure_listing_dir(string $id): string
{
    $root = uploads_root();
    if (!is_dir($root) && !mkdir($root, 0755, true) && !is_dir($root)) {
        throw new RuntimeException('Could not create the image folder.');
    }
    $dir = listing_dir($id);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the listing image folder.');
    }
    if (!path_inside_uploads($dir)) {
        throw new RuntimeException('Invalid image path.');
    }
    return (string) realpath($dir);
}

function owned_image(string $id, string $rel): bool
{
    if (!valid_id($id) || !preg_match('#^uploads/' . preg_quote($id, '#') . '/[a-f0-9]{16}\.jpg$#', $rel)) {
        return false;
    }
    $path = ROOT . '/' . $rel;
    return is_file($path) && path_inside_uploads($path);
}

function delete_listing_files(string $id): void
{
    if (!valid_id($id)) {
        return;
    }
    $dir = listing_dir($id);
    if (!is_dir($dir) || !path_inside_uploads($dir)) {
        return;
    }
    $real = realpath($dir);
    if ($real === false) {
        return;
    }
    foreach (scandir($real) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $real . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }
    rmdir($real);
}

function cleanup_orphans(string $id, array $keepRel): void
{
    if (!valid_id($id)) {
        return;
    }
    $dir = listing_dir($id);
    if (!is_dir($dir) || !path_inside_uploads($dir)) {
        return;
    }
    $real = realpath($dir);
    if ($real === false) {
        return;
    }
    $keep = [];
    foreach ($keepRel as $rel) {
        if (is_string($rel)) {
            $keep[basename($rel)] = true;
        }
    }
    foreach (scandir($real) ?: [] as $file) {
        if ($file === '.' || $file === '..' || isset($keep[$file])) {
            continue;
        }
        $path = $real . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }
    if (count($keep) === 0) {
        @rmdir($real);
    }
}

function normalize_uploads(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }
    $out = [];
    foreach ($files['name'] as $i => $name) {
        $out[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
            'error' => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($files['size'][$i] ?? 0),
        ];
    }
    return $out;
}

function store_upload(array $file, string $id): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('The image is too large. Each one must be under 8 MB.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image was not received. Please try again.');
    }
    if ((int) ($file['size'] ?? 0) > MAX_IMAGE_BYTES) {
        throw new RuntimeException('Each image must be under 8 MB.');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid image upload.');
    }
    $info = @getimagesize($tmp);
    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!$info || !in_array($info[2], $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG and WEBP images are allowed.');
    }
    if (($info[0] * $info[1]) > MAX_IMAGE_PIXELS) {
        throw new RuntimeException('The image resolution is too high.');
    }
    if ($info[2] === IMAGETYPE_WEBP && !function_exists('imagecreatefromwebp')) {
        throw new RuntimeException('The server does not support WEBP. Save the image as a JPG.');
    }
    if (!extension_loaded('gd')) {
        throw new RuntimeException('The server does not have PHP GD, so images cannot be resized.');
    }
    $dir = ensure_listing_dir($id);
    $name = bin2hex(random_bytes(8)) . '.jpg';
    image_to_jpeg($tmp, $dir . DIRECTORY_SEPARATOR . $name, (int) $info[2]);
    return 'uploads/' . $id . '/' . $name;
}

function image_to_jpeg(string $src, string $dest, int $type): void
{
    $img = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default => false,
    };
    if (!$img) {
        throw new RuntimeException('The image cannot be read.');
    }
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($src);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($img, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($img);
                $img = $rotated;
            }
        }
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min(1, MAX_IMAGE_EDGE / max($w, $h, 1));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $canvas = imagecreatetruecolor($nw, $nh);
    if ($canvas === false) {
        imagedestroy($img);
        throw new RuntimeException('The image was not saved.');
    }
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
    imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($img);
    imageinterlace($canvas, true);
    $ok = imagejpeg($canvas, $dest, 80);
    imagedestroy($canvas);
    if (!$ok) {
        throw new RuntimeException('The image was not saved.');
    }
}

function rollback_uploads(array $rels): void
{
    foreach ($rels as $rel) {
        if (!is_string($rel)) {
            continue;
        }
        $path = ROOT . '/' . $rel;
        if (is_file($path) && path_inside_uploads($path)) {
            unlink($path);
        }
    }
}
