<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TilesController extends Controller
{
    public function serve(Request $request, string $file): Response
    {
        $path = storage_path("app/tiles/{$file}");

        abort_if(! file_exists($path) || ! str_ends_with($file, '.pmtiles'), 404);

        $fileSize = filesize($path);
        $rangeHeader = $request->header('Range');

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Accept-Ranges' => 'bytes',
            'Content-Encoding' => 'identity',
            'Cache-Control' => 'public, max-age=86400',
        ];

        if ($rangeHeader && preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = (int) $matches[1];
            $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

            $handle = fopen($path, 'rb');
            fseek($handle, $start);
            $content = fread($handle, $end - $start + 1);
            fclose($handle);

            return response($content, 206, array_merge($headers, [
                'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                'Content-Length' => strlen($content),
            ]));
        }

        $content = file_get_contents($path);

        return response($content, 200, array_merge($headers, [
            'Content-Length' => strlen($content),
        ]));
    }
}
