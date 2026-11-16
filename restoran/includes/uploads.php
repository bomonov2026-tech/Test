<?php
declare(strict_types=1);

/**
 * Xavfsiz rasm yuklash: hajm, MIME (finfo), getimagesize, tasodifiy nom,
 * GD orqali qayta kodlash (EXIF va zararli yuklamalar olib tashlanadi).
 */

const UPLOAD_FOLDERS = ['products', 'categories', 'banners', 'logo'];

/**
 * @return array{ok: bool, path?: string, error?: string}
 */
function save_uploaded_image(array $file, string $folder): array
{
    if (!in_array($folder, UPLOAD_FOLDERS, true)) {
        return ['ok' => false, 'error' => 'Noto‘g‘ri papka'];
    }
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Rasm tanlanmagan'];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Rasm yuklashda xatolik (kod ' . $err . ')'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $max = (int) config('uploads.max_bytes', 5 * 1024 * 1024);
    $size = (int) ($file['size'] ?? 0);
    if (!is_uploaded_file($tmp) || $size <= 0) {
        return ['ok' => false, 'error' => 'Fayl yaroqsiz'];
    }
    if ($size > $max) {
        return ['ok' => false, 'error' => 'Rasm hajmi 5 MB dan oshmasligi kerak'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Faqat JPG, PNG yoki WEBP formatdagi rasm'];
    }

    $dir = UPLOAD_ROOT . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Yuklash papkasini yaratib bo‘lmadi'];
    }

    $format = $mime;
    if ($format === 'image/webp' && !function_exists('imagewebp')) {
        $format = 'image/jpeg';
    }
    $ext = $format === 'image/webp' ? 'webp' : ($format === 'image/png' ? 'png' : 'jpg');
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    $saved = false;
    if (extension_loaded('gd')) {
        $src = @imagecreatefromstring((string) file_get_contents($tmp));
        if ($src !== false) {
            $saved = write_resized_image($src, $format, $dest);
            imagedestroy($src);
        }
    }
    if (!$saved) {
        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'error' => 'Faylni saqlab bo‘lmadi'];
        }
    }
    @chmod($dest, 0644);

    return ['ok' => true, 'path' => 'uploads/' . $folder . '/' . $name];
}

function write_resized_image(GdImage $src, string $format, string $dest): bool
{
    $w = imagesx($src);
    $h = imagesy($src);
    $maxDim = (int) config('uploads.max_dimension', 1200);
    $scale = min(1.0, $maxDim / max($w, $h, 1));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    if ($format === 'image/jpeg') {
        $bg = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $bg);
    } else {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $ok = match ($format) {
        'image/png' => imagepng($dst, $dest, 6),
        'image/webp' => imagewebp($dst, $dest, 85),
        default => imagejpeg($dst, $dest, 85),
    };
    imagedestroy($dst);
    return (bool) $ok;
}

/** Yuklangan faylni (uploads ichida bo‘lsa) o‘chiradi. */
function delete_upload(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '' || !str_starts_with($relativePath, 'uploads/')) {
        return;
    }
    $full = realpath(PUBLIC_ROOT . '/' . $relativePath);
    $base = realpath(UPLOAD_ROOT);
    if ($full !== false && $base !== false && str_starts_with($full, $base . DIRECTORY_SEPARATOR) && is_file($full)) {
        @unlink($full);
    }
}
