<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 5:08 AM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

$path = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\frames\original\frame19.jpg';
$path2 = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\distorted.jpg';

/* Create new object */
$im = new Imagick($path);
$w1 = $im->getImageHeight();
$h1 = $im->getImageWidth();

//$points = array(
//    //0.2, 0.0, 0.0, 1.0
//    0.2, 0.1, 0.0, 1.0
//);
//$im->setimagebackgroundcolor("#fad888");
//$im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_EDGE);
//$im->distortImage(\Imagick::DISTORTION_BARRELINVERSE, $points, true);

//$points = array(
//    0
//);
//$im->setimagebackgroundcolor("#fad888");
//$im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_BACKGROUND);
//$im->distortImage(\Imagick::DISTORTION_DEPOLAR, $points, true);
//$im->scaleImage($h1, $w1);

//$points = array(
//    0
//);
//$im->setimagebackgroundcolor("#fad888");
//$im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_HORIZONTALTILE);
//$im->distortImage(\Imagick::DISTORTION_POLAR, $points, true);

$points = array(

    //Setup some control points that don't move
    5 * $im->getImageWidth() / 100, 5 * $im->getImageHeight() / 100,
    5 * $im->getImageWidth() / 100, 5 * $im->getImageHeight() / 100,
    5 * $im->getImageWidth() / 100, 95 * $im->getImageHeight() / 100,
    5 * $im->getImageWidth() / 100, 95 * $im->getImageHeight() / 100,
    95 * $im->getImageWidth() / 100, 95 * $im->getImageHeight() / 100,
    95 * $im->getImageWidth() / 100, 95 * $im->getImageHeight() / 100,
    5 * $im->getImageWidth() / 100, 5 * $im->getImageHeight() / 100,
    95 * $im->getImageWidth() / 100, 95 * $im->getImageHeight() / 100,
    //Move the centre of the image down and to the right
    50 * $im->getImageWidth() / 100, 50 * $im->getImageHeight() / 100,
    60 * $im->getImageWidth() / 100, 60 * $im->getImageHeight() / 100,
    //Move a point near the top-right of the image down and to the left and down
    90 * $im->getImageWidth(), 10 * $im->getImageHeight(),
    80 * $im->getImageWidth(), 15 * $im->getImageHeight(),
);
$im->setimagebackgroundcolor("#fad888");
$im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_EDGE);
$im->distortImage(\Imagick::DISTORTION_SHEPARDS, $points, true);


header("Content-Type: image/jpeg");
file_put_contents($path2, $im);