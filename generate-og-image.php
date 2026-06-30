<?php
/**
 * Generate Open Graph image for social sharing
 * 1200x630px JPG < 300KB
 * RACINE BY GANDA brand colors
 */

// Dimensions
$width = 1200;
$height = 630;

// Create image
$img = imagecreatetruecolor($width, $height);

// Colors RACINE
$black = imagecolorallocate($img, 22, 13, 12);     // #160D0C
$orange = imagecolorallocate($img, 237, 95, 30);   // #ED5F1E
$yellow = imagecolorallocate($img, 255, 184, 0);   // #FFB800
$white = imagecolorallocate($img, 255, 255, 255);  // #FFFFFF

// Background black
imagefilledrectangle($img, 0, 0, $width, $height, $black);

// Accent bar left (orange)
imagefilledrectangle($img, 0, 0, 8, $height, $orange);

// Circle decorative top-left (yellow, semi-transparent effect via pattern)
imagefilledellipse($img, 100, 100, 300, 300, $yellow);
imagesetthickness($img, 1);

// Circle decorative bottom-right (orange)
imagefilledellipse($img, 1100, 530, 400, 400, $orange);

// Apply transparency by layering
imagecolortransparent($img, $yellow);
$img2 = imagecreatetruecolor($width, $height);
imagefilledrectangle($img2, 0, 0, $width, $height, $black);
imagefilledellipse($img2, 100, 100, 300, 300, $yellow);
imagecopymerge($img, $img2, 0, 0, 0, 0, $width, $height, 15); // 15% opacity
imagefilledellipse($img2, 1100, 530, 400, 400, $orange);
imagecopymerge($img, $img2, 0, 0, 0, 0, $width, $height, 10); // 10% opacity
imagedestroy($img2);

// Reset accent bar (was covered by merge)
imagefilledrectangle($img, 0, 0, 8, $height, $orange);

// Font path (system fonts)
$fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
$fontRegular = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

if (!file_exists($fontBold)) {
    // Fallback: use default font
    $fontBold = 5; // GD built-in font
    $fontRegular = 3;
}

// Main title "RACINE BY GANDA"
if (is_string($fontBold)) {
    imagettftext($img, 70, 0, 150, 280, $yellow, $fontBold, 'RACINE BY GANDA');
} else {
    imagestring($img, $fontBold, 250, 220, 'RACINE BY GANDA', $yellow);
}

// Subtitle "Mode Africaine Contemporaine"
if (is_string($fontRegular)) {
    imagettftext($img, 32, 0, 300, 360, $orange, $fontRegular, 'Mode Africaine Contemporaine');
} else {
    imagestring($img, $fontRegular, 320, 320, 'Mode Africaine Contemporaine', $orange);
}

// Decorative line
imageline($img, 350, 400, 850, 400, $yellow);
imagesetthickness($img, 3);
imageline($img, 350, 400, 850, 400, $yellow);

// Bottom text "Créations Authentiques · Artisanat Premium"
if (is_string($fontRegular)) {
    imagettftext($img, 22, 0, 280, 540, $white, $fontRegular, 'Creations Authentiques · Artisanat Premium');
} else {
    imagestring($img, $fontRegular, 280, 500, 'Creations Authentiques · Artisanat Premium', $white);
}

// URL
if (is_string($fontRegular)) {
    imagettftext($img, 18, 0, 440, 590, $white, $fontRegular, 'racinebyganda.com');
} else {
    imagestring($img, $fontRegular, 460, 560, 'racinebyganda.com', $white);
}

// Save as JPG (quality 90)
imagejpeg($img, __DIR__ . '/public/images/og-image-racine.jpg', 90);

// Cleanup
imagedestroy($img);

echo "✅ OG image generated: public/images/og-image-racine.jpg\n";

// Check file size
$size = filesize(__DIR__ . '/public/images/og-image-racine.jpg');
echo "📏 File size: " . round($size / 1024, 2) . " KB\n";

if ($size > 300 * 1024) {
    echo "⚠️  Warning: File > 300KB, re-generating with quality 85...\n";
    $img = imagecreatefromjpeg(__DIR__ . '/public/images/og-image-racine.jpg');
    imagejpeg($img, __DIR__ . '/public/images/og-image-racine.jpg', 85);
    imagedestroy($img);
    $size = filesize(__DIR__ . '/public/images/og-image-racine.jpg');
    echo "📏 New size: " . round($size / 1024, 2) . " KB\n";
}
