<?php
// Spine generator using GD. Requires ext-gd enabled.
// Usage:
//   php scripts/generate_spines.php --in=assets/covers --out=assets/spines/generated
//   php scripts/generate_spines.php --text-only --text="My Title" --bg=#1b1b1b --fg=#f5f5f5
//   php scripts/generate_spines.php --fallback-text --bg=#1b1b1b --fg=#f5f5f5

$options = getopt('', [
    'in:',
    'out:',
    'text::',
    'bg::',
    'fg::',
    'text-only',
    'fallback-text'
]);
$inDir = isset($options['in']) ? $options['in'] : 'assets/covers';
$outDir = isset($options['out']) ? $options['out'] : 'assets/spines/generated';
$textOnly = array_key_exists('text-only', $options);
$fallbackText = array_key_exists('fallback-text', $options);
$textOverride = isset($options['text']) ? trim($options['text']) : '';
$bgHex = isset($options['bg']) ? $options['bg'] : '#1b1b1b';
$fgHex = isset($options['fg']) ? $options['fg'] : '#f5f5f5';

$root = realpath(__DIR__ . '/..');
$inPath = $root . DIRECTORY_SEPARATOR . $inDir;
$outPath = $root . DIRECTORY_SEPARATOR . $outDir;

if (!is_dir($inPath) && !$textOnly) {
    fwrite(STDERR, "Input directory not found: {$inPath}\n");
    exit(1);
}
if (!is_dir($outPath)) {
    mkdir($outPath, 0755, true);
}

$files = $textOnly ? ['__text_only__'] : glob($inPath . '/*.{jpg,jpeg,png}', GLOB_BRACE);
if (!$files) {
    fwrite(STDERR, "No cover images found in {$inPath}\n");
    exit(1);
}

$targetWidth = 120;
$targetHeight = 600;

foreach ($files as $file) {
    $img = null;
    $filename = $textOnly ? 'spine' : pathinfo($file, PATHINFO_FILENAME);

    if (!$textOnly) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = imagecreatefromjpeg($file);
        } elseif ($ext === 'png') {
            $img = imagecreatefrompng($file);
        }

        if (!$img && !$fallbackText) {
            fwrite(STDERR, "Failed to load {$file}\n");
            continue;
        }
    }

    $spine = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($spine, false);
    imagesavealpha($spine, true);
    $transparent = imagecolorallocatealpha($spine, 0, 0, 0, 127);
    imagefilledrectangle($spine, 0, 0, $targetWidth, $targetHeight, $transparent);

    if ($img) {
        $srcW = imagesx($img);
        $srcH = imagesy($img);

        // Crop a vertical strip from the cover to simulate a spine.
        $cropX = (int)($srcW * 0.05);
        $cropW = (int)($srcW * 0.15);
        if ($cropW < 1) {
            $cropW = max(1, (int)($srcW * 0.1));
        }

        imagecopyresampled(
            $spine,
            $img,
            0,
            0,
            $cropX,
            0,
            $targetWidth,
            $targetHeight,
            $cropW,
            $srcH
        );
    } else {
        $bg = bb_parse_hex_color($spine, $bgHex, [27, 27, 27]);
        imagefilledrectangle($spine, 0, 0, $targetWidth, $targetHeight, $bg);
    }

    if ($textOnly || !$img) {
        $label = $textOverride !== '' ? $textOverride : bb_title_from_filename($filename);
        $fg = bb_parse_hex_color($spine, $fgHex, [245, 245, 245]);
        bb_draw_spine_text($spine, $label, $fg);
    }

    $outFile = $outPath . DIRECTORY_SEPARATOR . $filename . '.png';
    imagepng($spine, $outFile);

    if ($img) {
        imagedestroy($img);
    }
    imagedestroy($spine);

    fwrite(STDOUT, "Wrote {$outFile}\n");
}

function bb_title_from_filename($filename)
{
    $label = str_replace(['_', '-'], ' ', $filename);
    $label = preg_replace('/\s+/', ' ', $label);
    return trim(ucwords($label));
}

function bb_parse_hex_color($img, $hex, $fallback)
{
    $hex = trim(str_replace('#', '', $hex));
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return imagecolorallocate($img, $fallback[0], $fallback[1], $fallback[2]);
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return imagecolorallocate($img, $r, $g, $b);
}

function bb_draw_spine_text($img, $text, $color)
{
    if ($text === '') {
        return;
    }
    $maxLen = 40;
    if (strlen($text) > $maxLen) {
        $text = substr($text, 0, $maxLen - 1) . '.';
    }
    $font = 3;
    $x = 6;
    $y = imagesy($img) - 6;
    imagestringup($img, $font, $x, $y, $text, $color);
}
