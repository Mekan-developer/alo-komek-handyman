<?php

namespace App\Enums;

enum CategoryIconType: string
{
    /** Icon picked from the curated preset set in config/service_icons.php. */
    case Preset = 'preset';

    /** Custom SVG file uploaded by the admin and stored on the public disk. */
    case Custom = 'custom';
}
