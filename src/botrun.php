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

//$res = $GM->giphyGet($path);
$res['url'] = 'https://media.giphy.com/media/eNKObbwDIJOy4/giphy.gif';

$GM->liquidRescale($path, $new_path);

$FB = new \ContentAwareBot\FacebookHelper();
$fb = $FB->init($_APP_ID, $_APP_SECRET, $_ACCESS_TOKEN_DEBUG);

if (isset($res['user']) && isset($res['url'])){
    $desc = "Original by {$res['user']}:\n{$res['url']}";
} else {
    if (isset($res['url'])) {
        $desc = "{$res['url']}";
    }
}

$title = md5(time ().mt_rand(0, 100)); // just a hash
$FB->postVideo($fb, $new_path, $title, $desc);