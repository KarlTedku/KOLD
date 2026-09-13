<?php

declare(strict_types=1);

$width = 1080;
$height = 1350;
$output = __DIR__.'/../public/assets/kold-beta-invite.png';
$displayFont = '/System/Library/Fonts/Supplemental/Georgia Bold.ttf';
$bodyFont = '/System/Library/Fonts/Hiragino Sans GB.ttc';

if (! extension_loaded('gd') || ! is_readable($bodyFont)) {
    fwrite(STDERR, "GD and a Traditional Chinese font are required.\n");
    exit(1);
}

$image = imagecreatetruecolor($width, $height);
imageantialias($image, true);

function betaColor(GdImage $image, string $hex): int
{
    $hex = ltrim($hex, '#');

    return imagecolorallocate(
        $image,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    );
}

function betaRoundedRectangle(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
{
    imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

function betaTextWidth(string $text, string $font, float $size): int
{
    $box = imagettfbbox($size, 0, $font, $text);

    return $box[2] - $box[0];
}

function betaCenteredText(GdImage $image, string $text, string $font, float $size, int $y, int $color, int $centerX): void
{
    $x = (int) ($centerX - betaTextWidth($text, $font, $size) / 2);
    imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
}

$ink = betaColor($image, '#10221f');
$forest = betaColor($image, '#214c42');
$paper = betaColor($image, '#f7f4ee');
$mist = betaColor($image, '#d7ebe4');
$coral = betaColor($image, '#ff8d70');
$muted = betaColor($image, '#9eb3ad');
$white = betaColor($image, '#ffffff');
$line = betaColor($image, '#d9ddd8');

imagefilledrectangle($image, 0, 0, $width, $height, $ink);
imagefilledrectangle($image, 0, 0, 18, $height, $coral);

imagettftext($image, 78, 0, 76, 122, $paper, $displayFont, 'KOL');
imagettftext($image, 78, 0, 242, 122, $coral, $displayFont, 'D');
imagettftext($image, 18, 0, 78, 185, $coral, $bodyFont, 'INVITED BETA  ·  約 5 分鐘完成');

imagettftext($image, 54, 0, 74, 285, $paper, $bodyFont, '建立你的 KOL');
imagettftext($image, 54, 0, 74, 360, $paper, $bodyFont, '專屬頁面');
imagettftext($image, 32, 0, 76, 435, $muted, $bodyFont, '讓品牌更容易找到你。');

$steps = [
    ['01', '建立 Profile'],
    ['02', '加入社群連結'],
    ['03', '確認 AI 標籤'],
    ['04', '預覽及發佈'],
];

foreach ($steps as $index => [$number, $label]) {
    $y = 565 + $index * 115;
    betaRoundedRectangle($image, 76, $y - 36, 130, $y + 18, 27, $index < 2 ? $coral : $forest);
    betaCenteredText($image, $number, $bodyFont, 13, $y - 1, $index < 2 ? $ink : $paper, 103);
    imagettftext($image, 23, 0, 156, $y, $paper, $bodyFont, $label);
    if ($index < count($steps) - 1) {
        imagefilledrectangle($image, 102, $y + 25, 105, $y + 72, $forest);
    }
}

betaRoundedRectangle($image, 610, 260, 1002, 1085, 30, $paper);
betaRoundedRectangle($image, 728, 305, 884, 461, 78, $ink);
betaCenteredText($image, 'KOLD', $displayFont, 30, 400, $paper, 806);
betaCenteredText($image, '你的名字', $bodyFont, 28, 515, $ink, 806);
betaCenteredText($image, '內容創作者 · Hong Kong', $bodyFont, 15, 552, $forest, 806);

$tags = ['生活', '美妝', '短影音'];
$tagX = 662;
foreach ($tags as $tag) {
    $tagWidth = betaTextWidth($tag, $bodyFont, 14) + 34;
    betaRoundedRectangle($image, $tagX, 585, $tagX + $tagWidth, 627, 21, $mist);
    imagettftext($image, 14, 0, $tagX + 17, 613, $forest, $bodyFont, $tag);
    $tagX += $tagWidth + 10;
}

foreach (['Instagram', 'YouTube', '作品集'] as $index => $label) {
    $top = 680 + $index * 92;
    betaRoundedRectangle($image, 660, $top, 952, $top + 64, 8, $white);
    imagerectangle($image, 660, $top, 952, $top + 64, $line);
    betaCenteredText($image, $label, $bodyFont, 17, $top + 41, $ink, 806);
}

betaCenteredText($image, 'kold.tedku.cloud/k/your-name', $bodyFont, 13, 1012, $forest, 806);

betaRoundedRectangle($image, 74, 1138, 1004, 1268, 12, $coral);
imagettftext($image, 27, 0, 112, 1193, $ink, $bodyFont, '免費建立 KOL Card');
imagettftext($image, 18, 0, 112, 1232, $ink, $bodyFont, 'Google 或 Meta 登入  ·  kold.tedku.cloud/beta');
imagettftext($image, 14, 0, 76, 1312, $muted, $bodyFont, '整理你的內容 · 分享專屬連結 · 尋找品牌合作');

imagepng($image, $output, 9);
imagedestroy($image);

echo $output.PHP_EOL;
