<?php

namespace App\Support;

use RuntimeException;

class PhotoConverter
{
    private const MAX_HEIGHT = 1800;

    private const WEBP_QUALITY = 85;

    private const CONTENT_MAX_WIDTH = 700;

    private const CONTENT_SIZE_THRESHOLD = 800 * 1024; // 800 KB

    private const CONTENT_WIDTH_THRESHOLD = 800;

    private const ICON_MAX_WIDTH = 400;

    private const ICON_TALL_HEIGHT = 700;

    private const ICON_TALL_TARGET_HEIGHT = 500;

    private const ICON_MAX_BYTES = 50 * 1024; // 50 KB

    private const ICON_MIN_QUALITY = 40;

    private const ICON_MIN_SIDE = 16;

    /**
     * Convert an image file to WebP, scaling down if height > 1080px.
     * Returns the absolute path of the new .webp file.
     */
    public static function convert(string $absolutePath): string
    {
        $info = @getimagesize($absolutePath);

        if ($info === false) {
            throw new RuntimeException("Cannot read image: {$absolutePath}");
        }

        $width = $info[0];
        $height = $info[1];
        $mimeType = $info['mime'];

        $image = self::loadImage($absolutePath, $mimeType);

        if ($height > self::MAX_HEIGHT) {
            $image = self::scaleDown($image, $width, $height);
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $absolutePath);

        if (imagewebp($image, $webpPath, self::WEBP_QUALITY) === false) {
            imagedestroy($image);
            throw new RuntimeException("Failed to write WebP: {$webpPath}");
        }

        imagedestroy($image);

        return $webpPath;
    }

    /**
     * Convert a category content image to WebP.
     * Resizes to 700 px wide (height proportional) when file > 800 KB AND width > 800 px.
     */
    public static function convertContent(string $absolutePath): string
    {
        $info = @getimagesize($absolutePath);

        if ($info === false) {
            throw new RuntimeException("Cannot read image: {$absolutePath}");
        }

        $width = $info[0];
        $height = $info[1];
        $mimeType = $info['mime'];
        $fileSize = (int) filesize($absolutePath);

        $image = self::loadImage($absolutePath, $mimeType);

        if ($fileSize > self::CONTENT_SIZE_THRESHOLD && $width > self::CONTENT_WIDTH_THRESHOLD) {
            $image = self::scaleToWidth($image, $width, $height, self::CONTENT_MAX_WIDTH);
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $absolutePath);

        if (imagewebp($image, $webpPath, self::WEBP_QUALITY) === false) {
            imagedestroy($image);
            throw new RuntimeException("Failed to write WebP: {$webpPath}");
        }

        imagedestroy($image);

        return $webpPath;
    }

    /**
     * Convert an image to WebP, resizing it to a target width (height stays proportional / auto).
     * Only scales down — images already narrower than the target keep their original size.
     * Returns the absolute path of the new .webp file.
     */
    public static function convertToWidth(string $absolutePath, int $targetWidth): string
    {
        $info = @getimagesize($absolutePath);

        if ($info === false) {
            throw new RuntimeException("Cannot read image: {$absolutePath}");
        }

        $width = $info[0];
        $height = $info[1];
        $mimeType = $info['mime'];

        $image = self::loadImage($absolutePath, $mimeType);

        if ($width > $targetWidth) {
            $image = self::scaleToWidth($image, $width, $height, $targetWidth);
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $absolutePath);

        if (imagewebp($image, $webpPath, self::WEBP_QUALITY) === false) {
            imagedestroy($image);
            throw new RuntimeException("Failed to write WebP: {$webpPath}");
        }

        imagedestroy($image);

        return $webpPath;
    }

    /**
     * Convert a category icon image to a WebP of at most 50 KB.
     * Width is capped at 400 px (height auto); if the height is still >= 700 px
     * it is shrunk to 500 px (width proportional). When the file is still over
     * 50 KB, WebP quality is lowered first and the dimensions shrunk after that.
     * Transparency is preserved. Returns the absolute path of the new .webp file.
     */
    public static function convertCategoryIcon(string $absolutePath): string
    {
        $info = @getimagesize($absolutePath);

        if ($info === false) {
            throw new RuntimeException("Cannot read image: {$absolutePath}");
        }

        $width = $info[0];
        $height = $info[1];
        $source = self::loadImage($absolutePath, $info['mime']);

        if ($width > self::ICON_MAX_WIDTH) {
            $height = (int) max(1, round($height * (self::ICON_MAX_WIDTH / $width)));
            $width = self::ICON_MAX_WIDTH;
        }

        if ($height >= self::ICON_TALL_HEIGHT) {
            $width = (int) max(1, round($width * (self::ICON_TALL_TARGET_HEIGHT / $height)));
            $height = self::ICON_TALL_TARGET_HEIGHT;
        }

        $webp = self::encodeWithinLimit($source, $width, $height);
        imagedestroy($source);

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $absolutePath);

        if (file_put_contents($webpPath, $webp) === false) {
            throw new RuntimeException("Failed to write WebP: {$webpPath}");
        }

        return $webpPath;
    }

    /**
     * Encode the source at the given size, stepping quality and then dimensions
     * down until the WebP fits ICON_MAX_BYTES (or the image can't get smaller).
     *
     * @param  \GdImage  $source
     */
    private static function encodeWithinLimit(mixed $source, int $width, int $height): string
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        while (true) {
            $canvas = self::resampleWithAlpha($source, $sourceWidth, $sourceHeight, $width, $height);

            for ($quality = self::WEBP_QUALITY; $quality >= self::ICON_MIN_QUALITY; $quality -= 15) {
                $webp = self::encodeWebp($canvas, $quality);

                if (strlen($webp) <= self::ICON_MAX_BYTES) {
                    imagedestroy($canvas);

                    return $webp;
                }
            }

            imagedestroy($canvas);

            if ($width <= self::ICON_MIN_SIDE || $height <= self::ICON_MIN_SIDE) {
                return $webp;
            }

            $width = (int) max(1, round($width * 0.8));
            $height = (int) max(1, round($height * 0.8));
        }
    }

    /**
     * @param  \GdImage  $source
     * @return \GdImage
     */
    private static function resampleWithAlpha(mixed $source, int $sourceWidth, int $sourceHeight, int $width, int $height): mixed
    {
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            throw new RuntimeException('Failed to create resized canvas.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        return $canvas;
    }

    /** @param \GdImage $image */
    private static function encodeWebp(mixed $image, int $quality): string
    {
        ob_start();
        $ok = imagewebp($image, null, $quality);
        $data = (string) ob_get_clean();

        if ($ok === false || $data === '') {
            throw new RuntimeException('Failed to encode WebP.');
        }

        return $data;
    }

    /** @return \GdImage */
    private static function loadImage(string $path, string $mimeType): mixed
    {
        $image = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException("Unsupported image type: {$mimeType}"),
        };

        if ($image === false) {
            throw new RuntimeException("Failed to load image: {$path}");
        }

        return $image;
    }

    /** @param \GdImage $image */
    private static function scaleDown(mixed $image, int $width, int $height): mixed
    {
        $ratio = self::MAX_HEIGHT / $height;
        $newWidth = (int) round($width * $ratio);

        $resized = imagecreatetruecolor($newWidth, self::MAX_HEIGHT);

        if ($resized === false) {
            imagedestroy($image);
            throw new RuntimeException('Failed to create resized canvas.');
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, self::MAX_HEIGHT, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /** @param \GdImage $image */
    private static function scaleToWidth(mixed $image, int $width, int $height, int $newWidth): mixed
    {
        $newHeight = (int) round($height * ($newWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($resized === false) {
            imagedestroy($image);
            throw new RuntimeException('Failed to create resized canvas.');
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
