<?php
session_start();

$code = rand(1000, 9999);
$_SESSION['captcha_code'] = $code;

header('Content-Type: image/png');

$width = 100;
$height = 40;
$image = imagecreatetruecolor($width, $height);

$bg = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 79, 70, 229); // Brand Indigo
$line_color = imagecolorallocate($image, 226, 232, 240);

imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Add some noise lines
for($i=0; $i<5; $i++) {
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $line_color);
}

// Write the code
$font = 5; // Built-in font
$x = 30;
$y = 12;
imagestring($image, $font, $x, $y, $code, $text_color);

imagepng($image);
imagedestroy($image);
?>
