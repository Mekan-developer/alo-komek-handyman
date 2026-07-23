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
