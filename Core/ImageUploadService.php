<?php
declare(strict_types=1);

namespace Core;

final class ImageUploadService
{
    private const MAX_BYTES = 3_145_728; // 3 MB

    /** actual MIME (from finfo) => file extension */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * @param array|null $file  A single entry from $_FILES (e.g. $_FILES['photo']).
     * @param string     $subdir  Sub-folder under public/uploads (e.g. 'students').
     * @return string  Web path to the stored image, or '' if no file was uploaded.
     * @throws \RuntimeException on any validation/move failure.
     */
    public function handle(?array $file, string $subdir): string
    {
        // No file chosen — not an error, just nothing to do.
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->errorMessage((int)$file['error']));
        }

        if (($file['size'] ?? 0) <= 0) {
            throw new \RuntimeException('Uploaded file is empty.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Image is too large (max 3 MB).');
        }

        // Guard against spoofed extensions: read the REAL mime from the bytes.
        $tmp  = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            throw new \RuntimeException('Invalid upload.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp) ?: '';
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Unsupported image type. Use JPG, PNG, WEBP, or GIF.');
        }

        $ext = self::ALLOWED[$mime];

        // public/ is one level up from Core/, i.e. project_root/public
        $subdir  = trim(preg_replace('/[^a-z0-9_-]/i', '', $subdir), '/');
        $baseDir = dirname(__DIR__) . '/public/uploads/' . $subdir;

        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            throw new \RuntimeException('Could not create upload directory.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $baseDir . '/' . $name;

        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('Failed to store the uploaded image.');
        }

        // Web-accessible path (public/ is the doc root).
        return '/uploads/' . $subdir . '/' . $name;
    }

    /**
     * Delete a previously-stored image given its web path. Silently ignores a
     * missing file. Used when replacing a photo on update.
     */
    public function delete(string $webPath): void
    {
        if ($webPath === '' || !str_starts_with($webPath, '/uploads/')) {
            return;
        }
        $full = dirname(__DIR__) . '/public' . $webPath;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image exceeds the maximum allowed size.',
            UPLOAD_ERR_PARTIAL                         => 'The image was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR                      => 'Server is missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE                      => 'Server failed to write the image to disk.',
            UPLOAD_ERR_EXTENSION                       => 'A server extension blocked the upload.',
            default                                    => 'Unknown upload error.',
        };
    }
}