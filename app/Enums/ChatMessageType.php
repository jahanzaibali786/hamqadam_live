<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatMessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Voice = 'voice';
    case Mixed = 'mixed';
    /** Animated GIF sent by URL (Tenor) — no upload row, url in metadata. */
    case Gif = 'gif';
}
