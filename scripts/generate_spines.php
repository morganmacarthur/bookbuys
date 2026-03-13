<?php
// Spine generator using GD. Requires ext-gd enabled.
// Usage: php scripts/generate_spines.php --in=assets/covers --out=assets/spines/generated

$options = getopt('', ['in:', 'out:']);
$inDir = isset($options['in']) ? $options['in'] : 'assets/covers';
$outDir = isset($options['out']) ? $options['out'] : 'assets/spines/generated';

$root = realpath(__DIR__ . '/..');
$inPath = $root . DIRECTORY_SEPARATOR . $inDir;
$outPath = $root . DIRECTORY_SEPARATOR . $outDir;

if (!is_dir($inPath)) {
    fwrite(STDERR, "Input directory not found: {$inPath}\n");
    exit(1);
}
if (!is_dir($outPath)) {
    mkdir($outPath, 0755, true);
}

$files = glob($inPath . '/*.{jpg,jpeg,png}', GLOB_BRACE);
if (!$files) {
    fwrite(STDERR, "No cover images found in {$inPath}\n");
    exit(1);
}

$targetWidth = 120;
$targetHeight = 600;

foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = imagecreatefromjpeg($file);
    } elseif ($ext === 'png') {
        $img = imagecreatefrompng($file);
    } else {
        continue;
    }

    if (!$img) {
        fwrite(STDERR, "Failed to load {$file}\n");
        continue;
    }

    $srcW = imagesx($img);
    $srcH = imagesy($img);

    // Crop a vertical strip from the cover to simulate a spine.
    $cropX = (int)($srcW * 0.05);
    $cropW = (int)($srcW * 0.15);
    if ($cropW < 1) {
        $cropW = max(1, (int)($srcW * 0.1));
    }

    $spine = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($spine, false);
    imagesavealpha($spine, true);
    $transparent = imagecolorallocatealpha($spine, 0, 0, 0, 127);
    imagefilledrectangle($spine, 0, 0, $targetWidth, $targetHeight, $transparent);

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

    $filename = pathinfo($file, PATHINFO_FILENAME);
    $outFile = $outPath . DIRECTORY_SEPARATOR . $filename . '.png';
    imagepng($spine, $outFile);

    imagedestroy($img);
    imagedestroy($spine);

    fwrite(STDOUT, "Wrote {$outFile}\n");
}
