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

$new_path = __DIR__."/resources/gifs/modified.gif";
$path = __DIR__."/resources/gifs/original.gif";

$res = $GM->giphyGet($path);

$GM->liquidRescale($path, $new_path);

$gif_id = $GM->giphyUpload($new_path);

if (!empty($gif_id)) {

    $FB = new \ContentAwareBot\FacebookHelper();
    $fb = $FB->init($_APP_ID, $_APP_SECRET, $_ACCESS_TOKEN_DEBUG);

    if (isset($res['user']) && isset($res['url'])){
        $desc = "Original by {$res['user']}:\n{$res['url']}";
    } else {
        if (isset($res['url'])) {
            $desc = "{$res['url']}";
        }
    }

    $first_frame = __DIR__.'/resources/frames/modified/frame0.jpg';
    $title = 'This is a test to circumvent Zuckerberg';
    $generated_gif = 'https://media.giphy.com/media/'.$gif_id.'/giphy.gif';
    $FB->newPost($fb, $first_frame, $title, $generated_gif, $res['url']);
} else {
    $this->logdata('Giphy id was empty');
}

