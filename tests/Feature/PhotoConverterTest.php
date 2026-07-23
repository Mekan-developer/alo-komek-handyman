<?php

namespace Tests\Feature;

use App\Support\PhotoConverter;
use Tests\TestCase;

class PhotoConverterTest extends TestCase
{
    private function createJpeg(int $width, int $height, string $path): void
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 100, 150, 200);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }

    public function test_converts_jpeg_to_webp(): void
    {
        $dir = sys_get_temp_dir().'/photo_converter_test_'.uniqid();
        mkdir($dir);
        $jpegPath = $dir.'/test.jpg';

        $this->createJpeg(800, 600, $jpegPath);

        $webpPath = PhotoConverter::convert($jpegPath);

        $this->assertFileExists($webpPath);
        $this->assertStringEndsWith('.webp', $webpPath);

        [$w, $h] = getimagesize($webpPath);
        $this->assertEquals(800, $w);
        $this->assertEquals(600, $h);

        unlink($jpegPath);
        unlink($webpPath);
        rmdir($dir);
    }

    public function test_scales_down_when_height_exceeds_1800px(): void
    {
        $dir = sys_get_temp_dir().'/photo_converter_test_'.uniqid();
        mkdir($dir);
        $jpegPath = $dir.'/tall.jpg';

        $this->createJpeg(1200, 2400, $jpegPath);

        $webpPath = PhotoConverter::convert($jpegPath);

        $this->assertFileExists($webpPath);

        [$w, $h] = getimagesize($webpPath);
        $this->assertEquals(1800, $h);
        $this->assertEquals(900, $w);

        unlink($jpegPath);
        unlink($webpPath);
        rmdir($dir);
    }

    public function test_does_not_scale_when_height_equals_1800px(): void
    {
        $dir = sys_get_temp_dir().'/photo_converter_test_'.uniqid();
        mkdir($dir);
        $jpegPath = $dir.'/exact.jpg';

        $this->createJpeg(1200, 1800, $jpegPath);

        $webpPath = PhotoConverter::convert($jpegPath);

        [$w, $h] = getimagesize($webpPath);
        $this->assertEquals(1800, $h);
        $this->assertEquals(1200, $w);

        unlink($jpegPath);
        unlink($webpPath);
        rmdir($dir);
    }

    public function test_throws_on_unsupported_mime_type(): void
    {
        $dir = sys_get_temp_dir().'/photo_converter_test_'.uniqid();
        mkdir($dir);
        $fakePath = $dir.'/file.bmp';
        file_put_contents($fakePath, 'BM fake bitmap content');

        $this->expectException(\RuntimeException::class);

        try {
            PhotoConverter::convert($fakePath);
        } finally {
            @unlink($fakePath);
            rmdir($dir);
        }
    }
}
