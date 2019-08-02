<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 5:08 AM
 */

require __DIR__ . '/../vendor/autoload.php';
require_once 'resources/secrets.php';

$path = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\frames\modified\frame10.jpg';
//$path = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\frames\modified\frame11.jpg';

/* Create new object */
/** @var \Imagick $src */
$src = new Imagick($path);
// Fully transparent images fuck me up, replace them with previous image, assuming its not the first frame
$output = $src->identifyImage(true);

echo $output;