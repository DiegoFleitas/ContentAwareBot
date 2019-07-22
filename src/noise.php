<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 5:08 AM
 */

$path = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\44597691_2272518159488552_3925472152878317568_o.jpg';

/* Create new object */
$im = new Imagick($path);

/* Scale down */
/**
 * NOISE_UNIFORM = 1;
 * NOISE_GAUSSIAN = 2; a
 * NOISE_MULTIPLICATIVEGAUSSIAN = 3; b
 * NOISE_IMPULSE = 4;
 * NOISE_LAPLACIAN = 5;
 * NOISE_POISSON = 6; c
 * NOISE_RANDOM = 7;
 */
$noiseType = 6;
$im->addNoiseImage($noiseType);

/* Display */
header('Content-Type: image/jpg');
//$data = $Imagick->getImageBlob ();
//echo $data;
file_put_contents ('test'.$noiseType.'.png', $im);