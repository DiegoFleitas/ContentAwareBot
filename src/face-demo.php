<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 5:08 AM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

$path = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\frames\original\frame42.jpg';
$path2 = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\zoom\test.jpg';

/* Create new object */
$im = new Imagick($path);
$w1 = $im->getImageHeight();
$h1 = $im->getImageWidth();

$GM = new \ContentAwareBot\GifManipulator();
$GM->ZoomToFaceOnce($im);

//$im->cropImage(461, 248, 0, 0);
//$im->scaleImage($h1, $w1);

//$liquidw = round($im->getImageWidth()*0.5);
//$liquidh = round($im->getImageHeight()*0.5);
//
//$im->liquidRescaleImage($liquidw, $liquidh, 15, 1);

/* Display */
header('Content-Type: image/jpg');
file_put_contents($path2, $im);