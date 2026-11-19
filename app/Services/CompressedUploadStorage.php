<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompressedUploadStorage
{
    public function store(UploadedFile $file, string $directory, string $disk): string
    {
        $mime = $file->getMimeType();
        $image = in_array($mime, ['image/jpeg', 'image/png'], true)
            ? @imagecreatefromstring((string) file_get_contents($file->getRealPath()))
            : false;

        if ($image === false || ! function_exists('imagewebp')) {
            return $this->storeOriginal($file, $directory, $disk);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, 3000 / max($width, $height));
        $output = $image;

        if ($scale < 1) {
            $output = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            imagealphablending($output, false);
            imagesavealpha($output, true);
            $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
            if ($transparent === false) {
                imagedestroy($output);
                imagedestroy($image);

                return $this->storeOriginal($file, $directory, $disk);
            }
            imagefill($output, 0, 0, $transparent);
            imagecopyresampled($output, $image, 0, 0, 0, 0, imagesx($output), imagesy($output), $width, $height);
        }

        ob_start();
        $encoded = imagewebp($output, null, 90);
        $compressed = ob_get_clean();
        imagedestroy($image);
        if ($output !== $image) {
            imagedestroy($output);
        }

        if (! $encoded || ! is_string($compressed) || strlen($compressed) >= $file->getSize()) {
            return $this->storeOriginal($file, $directory, $disk);
        }

        $path = trim($directory, '/').'/'.Str::random(40).'.webp';
        if (! Storage::disk($disk)->put($path, $compressed)) {
            throw new \RuntimeException('Gagal menyimpan gambar yang telah dikompres.');
        }

        return $path;
    }

    private function storeOriginal(UploadedFile $file, string $directory, string $disk): string
    {
        $path = $file->store($directory, $disk);

        if (! is_string($path)) {
            throw new \RuntimeException('Gagal menyimpan berkas unggahan.');
        }

        return $path;
    }
}
