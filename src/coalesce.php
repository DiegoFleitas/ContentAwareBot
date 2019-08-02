<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 5:08 AM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

use ContentAwareBot\Classes\GifManipulator;

$new_path = __DIR__.'\resources\gifs\modified.gif';
$path = __DIR__.'\resources\gifs\original.gif';


$output = $src2->identifyFormat('%n');

$original_frames = __DIR__.'/resources/frames/original/';


// For each frame
$res = scandir($original_frames);
$res = array_splice($res,2); // remove first two, hidden shit
$framecount = count($res);

$new_frames = [];


$modified_frames = __DIR__.'\resources\frames\modified\\';
for ($key = 0; $key < $framecount; $key++) {
    $modified_path = $modified_frames."frame{$key}.jpg";
    array_push($new_frames, $modified_path);
}

$delays = [];
$src2 = new \Imagick($path);
// Different frames might have different delay
foreach ($src2 as $frame) {
    array_push($delays, $src2->getImageDelay());
}

$GM = new GifManipulator();
$finalImage = $GM->coalesceImages($new_frames, $delays);
header("Content-Type: image/gif");
$finalImage->writeImages($new_path, true);