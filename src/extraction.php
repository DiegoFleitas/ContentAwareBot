<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 5:08 AM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

use ContentAwareBot\Classes\DataLogger;
use ContentAwareBot\Classes\GifManipulator;
use ContentAwareBot\Classes\FacebookHelper;

$dt = new DataLogger();
$dt->logdata('[DAILY]');

$GM = new GifManipulator();

$new_path = __DIR__.'/resources/gifs/modified.gif';
$path = __DIR__.'/resources/gifs/original.gif';
//$path = __DIR__.'\resources\gifs\test.gif';

$delays = [];
$src2 = new \Imagick($path);
// Different frames might have different delay
foreach ($src2 as $frame) {
    array_push($delays, $src2->getImageDelay());
}


//$GM->liquidRescale($path, $new_path);
$GM->initFfmpeg($_FFMPEG_PATH, $_FFPROBE_PATH);
$GM->initFfprobe($_FFMPEG_PATH, $_FFPROBE_PATH);
$GM->extractFrames2($path, $delays);