<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 11:04 PM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

use ContentAwareBot\Classes\DataLogger;
use ContentAwareBot\Classes\GifManipulator;
use ContentAwareBot\Classes\FacebookHelper;

$dt = new DataLogger();
$dt->logdata('[DAILY]');

$GM = new GifManipulator();

$new_path = __DIR__.'\resources\gifs\modified.gif';
$path = __DIR__.'\resources\gifs\original.gif';

$res = $GM->setAPIKEY($_GIPHY_API_KEY);
$res = $GM->giphyGet($path);

$GM->initFfmpeg($_FFMPEG_PATH, $_FFPROBE_PATH);
$GM->initFfprobe($_FFMPEG_PATH, $_FFPROBE_PATH);
$GM->liquidRescale2($path, $new_path);

$gif_id = $GM->giphyUpload($new_path);

if (!empty($gif_id)) {

    $FB = new FacebookHelper();
    $fb = $FB->init($_APP_ID, $_APP_SECRET, $_ACCESS_TOKEN_DEBUG);

    if (isset($res['user']) && isset($res['url'])){
        $desc = "Original by {$res['user']}:\n{$res['url']}";
    } else {
        if (isset($res['url'])) {
            $desc = "{$res['url']}";
        }
    }

    $first_frame = __DIR__.'/resources/frames/modified/frame0.jpg';
    $title = '';
    $generated_gif = 'https://media.giphy.com/media/'.$gif_id.'/giphy.gif';
    $FB->newPost($fb, $first_frame, $title, $generated_gif, $res['url']);
} else {
    $this->logdata('Giphy id was empty');
}

