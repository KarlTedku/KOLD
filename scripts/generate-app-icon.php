<?php

declare(strict_types=1);

$size = 1024;
$out = __DIR__.'/../public/assets/kold-app-icon-1024.png';

$img = imagecreatetruecolor($size, $size);
imagealphablending($img, true);
imagesavealpha($img, true);

function color($img, string $hex, int $alpha = 0): int
{
    $hex = ltrim($hex, '#');

    return imagecolorallocatealpha(
        $img,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
        $alpha
    );
}

function centerText($img, string $text, string $font, float $size, int $y, int $color, int $tracking = 0): void
{
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $width = 0;
    $boxes = [];

    foreach ($chars as $char) {
        $box = imagettfbbox($size, 0, $font, $char);
        $charWidth = $box[2] - $box[0];
        $boxes[] = [$char, $charWidth];
        $width += $charWidth;
    }

    $width += max(0, count($chars) - 1) * $tracking;
    $x = (int) ((imagesx($img) - $width) / 2);

    foreach ($boxes as [$char, $charWidth]) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $char);
        $x += $charWidth + $tracking;
    }
}

$ink = color($img, '#0f1c1a');
$paper = color($img, '#f7f4ee');
$coral = color($img, '#e36a4a');

imagefilledrectangle($img, 0, 0, $size, $size, $ink);

$displayFont = '/System/Library/Fonts/Supplemental/Georgia Bold.ttf';

if (! is_readable($displayFont)) {
    $displayFont = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
}

centerText($img, 'KOL', $displayFont, 248, 518, $paper, 2);

$box = imagettfbbox(286, 0, $displayFont, 'D');
$dWidth = $box[2] - $box[0];
imagettftext($img, 286, 0, (int) (($size - $dWidth) / 2), 775, $coral, $displayFont, 'D');

imagefilledrectangle($img, 336, 824, 688, 844, $coral);

imagepng($img, $out, 9);
imagedestroy($img);

echo $out.PHP_EOL;
