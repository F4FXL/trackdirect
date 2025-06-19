<?php
// dotColor.php — avec antialiasing

$color = isset($_GET['color']) ? $_GET['color'] : '000000';
$color = ltrim($color, '#');
$color = substr($color, 0, 6);

if (!preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
    $color = '000000';
}
list($r, $g, $b) = sscanf($color, "%02x%02x%02x");

// Facteur de suréchantillonnage (4× plus grand)
$scale = 4;
$size = 12;
$bigSize = $size * $scale;
$radius = 3 * $scale; // diamètre 6 → rayon 3

// Crée une grande image transparente
$bigImg = imagecreatetruecolor($bigSize, $bigSize);
imagesavealpha($bigImg, true);
$trans = imagecolorallocatealpha($bigImg, 0, 0, 0, 127);
imagefill($bigImg, 0, 0, $trans);

// Couleur du rond
$dot = imagecolorallocate($bigImg, $r, $g, $b);

// Dessine un rond au centre, diamètre 6*4=24
imagefilledellipse($bigImg, $bigSize/2, $bigSize/2, $radius*2, $radius*2, $dot);

// Redimensionne avec interpolation vers 12x12
$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);
imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
imagecopyresampled($img, $bigImg, 0, 0, 0, 0, $size, $size, $bigSize, $bigSize);


header('Pragma: public');
header('Cache-Control: max-age=86400, public');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
header('Content-type: image/png');
imagepng($img);
imagedestroy($bigImg);
imagedestroy($img);
