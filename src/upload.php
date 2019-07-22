<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 11:04 PM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

$GM = new \ContentAwareBot\GifManipulator();

$new_path = "C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\\resources\gifs\modified.gif";
$path = "C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\\resources\gifs\original.gif";

$res = $GM->giphyUpload($new_path);
//$res = $GM->giphyUpload('modified.gif');
//$res = $GM->gist($new_path);