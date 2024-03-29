<?php
/** Created by PhpStorm.
 * User: Diego
 * Date: 2/25/2019
 * Time: 11:00 PM */

namespace ContentAwareBot\Classes;

use FFMpeg;

class GifManipulator extends DataLogger
{
    /** @var $ffmpeg FFMpeg\FFMpeg   */
    protected $ffmpeg = null;
    /** @var $ffprobe FFMpeg\FFProbe  */
    protected $ffprobe = null;

    const NOISE_GAUSSIAN = 2;
    const NOISE_MULTIPLICATIVEGAUSSIAN = 3;
    const NOISE_POISSON = 6;

    protected $API_KEY = '';

    protected $KEYFRAME_ONETHIRD;
    protected $KEYFRAME_TWOTHIRDS;
    protected $KEYFRAME_ANTEPENULTIMATE;
    protected $KEYFRAME_PENULTIMATE;

    protected $GETCOORDS = true;
    protected $W;
    protected $H;
    protected $X;
    protected $Y;

    /**
     * @param string $API_KEY
     */
    public function setAPIKEY($API_KEY)
    {
        $this->API_KEY = $API_KEY;
    }

    /**
     * @param int $total
     */
    public function setRelevantFrames($total) {
        $onethird_total = $total / 3;
        $twothirds_total = $onethird_total * 2;
        /** one third of the way from $twothirds_total to $total */
        $antepenult = round((2/3) * $twothirds_total + (1/3) * $total);
        /** two thirds of the way from $twothirds_total to $total */
        $penult =  round($twothirds_total + ($antepenult - $twothirds_total) * 2);

        $this->KEYFRAME_ONETHIRD = $onethird_total;
        $this->KEYFRAME_TWOTHIRDS = $twothirds_total;
        $this->KEYFRAME_ANTEPENULTIMATE = $antepenult;
        $this->KEYFRAME_PENULTIMATE = $penult;
    }

    /**
     * @param \Imagick $im
     * @param int|string $key
     * @throws \ImagickException
     */
    public function Zoom($im, $key){
        $w1 = $im->getImageHeight();
        $h1 = $im->getImageWidth();

         $width = $this->W;
         $height = $this->H;
         $x = $this->X;
         $y = $this->Y;

        $this->logdata("frame {$key} cropping {$x},{$y},{$width},{$height}");

        $im->cropImage($width, $height, $x, $y);
        // FIXME: sometimes the cropped area is too pixelated when scaled
        $im->scaleImage($h1, $w1);
    }

    /**
     * @param \Imagick $im
     * @param int|string $frame
     * @throws \ImagickException
     */
    public function ZoomToFaceOnce($im, $frame){
        $w1 = $im->getImageHeight();
        $h1 = $im->getImageWidth();

        if ($this->GETCOORDS) {
            $path = $im->getImageFilename();

//            $coodinates = [];
            $coodinates = $this->deepAiFacialRecognition($path);

            if (count($coodinates)) {
                $this->logdata("frame {$frame} zoomed to face and rescaled");

                $facecoord = $this->pickBestFace($coodinates);

                $width = $facecoord[2];
                $height = $facecoord[3];
                $x = $facecoord[0];
                $y = $facecoord[1];

            } else {
                $this->logdata("frame {$frame} unable to find face, zoomed randomly and rescaled");

                /** average between actual width and 2/3 of it */
                $auxw = ($w1 + $w1 * (2/3)) / 2;
                /** average between actual height and 2/3 of it */
                $auxh = ($h1 + $h1 * (2/3)) / 2;

                $cropw = mt_rand($auxw, $w1);
                $croph =  mt_rand($auxh, $h1);

                $width = $cropw;
                $height = $croph;
                $x = 0;
                $y = 0;

                $this->GETCOORDS = false;
            }

            $im->cropImage($width, $height, $x, $y);
//            $im->scaleImage($h1, $w1);

            $this->setCoords($width, $height, $x, $y);

        } else {
            $this->Zoom($im, $frame);
        }
    }

    /**
     * @param string $width
     * @param string $height
     * @param string $x
     * @param string $y
     */
    public function setCoords($width, $height, $x, $y) {
        $this->W = $width;
        $this->H = $height;
        $this->X = $x;
        $this->Y = $y;
    }

    /**
     * @param string $dir
     * @param null $total
     */
    public function deleteFrames($dir = '', $total = null){

        if($total == null){
            $res = scandir($dir);
            // remove first two, hidden shit
            $res = array_splice($res, 2);

            // Delete frames
            foreach ($res as $file) {
                // delete file
                unlink($dir.$file);
            }

            $i = count($res);

        } else {
            // Delete frames
            for ( $i = 0; $i < $total; $i++) {
                $file = $dir."frame{$i}.jpg";
                if (is_file($file)) {
                    // delete file
                    unlink($file);
                }
            }
        }

        $this->logdata("{$i} frames deleted from {$dir}");

    }


    /**
     * @param string $path
     * @param string $type
     *
     * @return array
     *
     * @throws \GPH\ApiException
     */
    public function giphyGet($path, $type = 'random'){

        $this->logdata('giphyGet '.$type.'...');

        $api_instance = new \GPH\Api\DefaultApi();
        $api_key = $this->API_KEY; // string | Giphy API Key.
        $limit = 1; // int | The maximum number of records to return.
        $rating = "g"; // string | Filters results by specified rating.
        $fmt = "json"; // string | Used to indicate the expected response format. Default is Json.

        try {
            /** @var \GPH\Model\InlineResponse200 $result */
            if($type == 'trending') {
                $result = $api_instance->gifsTrendingGet($api_key, $limit, $rating, $fmt);
            } else {
                $result = $api_instance->gifsRandomGet($api_key, $limit, $rating, $fmt);
            }

            // FIXME: $data->getId() might be empty
            if ($type == 'trending') {
                /** @var \GPH\Model\Gif $data */
                $data = $result->getData();
                $user = $data->getUsername();
                $url = "https://media.giphy.com/media/{$data->getId()}/giphy.gif";
                header('Content-type: image/gif');
                file_put_contents($path, file_get_contents($url));
            } else {
                /** @var \GPH\Model\RandomGif $data */
                $data = $result->getData();
                $user = '';
                $url = "https://media.giphy.com/media/{$data->getId()}/giphy.gif";
                $this->logdata('giphyGet '.$url.' saving at '.$path);
                header('Content-type: image/gif');
                file_put_contents($path, file_get_contents($url));
            }

            return [
                'user' => $user,
                'url' => $url
            ];

        } catch (Exception $e) {
            $message = 'Exception when calling DefaultApi->gifsTrendingGet: '. $e->getMessage();
            $this->logdata($message, 1);
        }

    }


    /**
     * @param string $path
     *
     * @return string
     *
     * @throws \GPH\ApiException
     */
    public function giphyUpload($path){

        $this->logdata('giphyUpload ...');

        try {

            $url = 'http://upload.giphy.com/v1/gifs';

            $api_key = $this->API_KEY; // string | Giphy API Key.
            $tags = 'contentaware, brokengif';

            // and here's how you'd use it
            $ch = curl_init($url);
            $ch = $this->buildMultiPartRequest($ch, "WebKitFormBoundary7MA4YWxkTrZu0gW",
                ['api_key' => $api_key, 'tags' => $tags], ['file' => file_get_contents($path)]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //            $response_header = substr($response, 0, $header_size);
            $response_body = substr($response, $header_size);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //            $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
            $err = curl_error($ch);

            curl_close($ch);

            if ($err) {
                $message = ' cURL Error #:' . $err.'  url: '.$url.' response: '.$response_body;
                $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
            } else {
                if ($httpcode != '200') {
                    $message =  ' Http code error #:' . $httpcode.' error: '.$err.' url: '.$url.' response: '.$response_body;
                    $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
                } else {
    //                $message =  'deepAI response: '.$response_body;
    //                $this->logdata($message);
                    $json = json_decode($response_body, true);
                    if (!empty($json)) {
                        // check if returned error
                        if(isset($json['meta']['status']) && $json['meta']['status'] == '200') {
                            return $json['data']['id'];
                        } else {
                            $message =  ' Something went wrong, Giphy response : '.$response_body;
                            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $message = 'Exception when calling DefaultApi->gifsTrendingGet: '. $e->getMessage();
            $this->logdata($message, 1);
        }

    }

    /**
     * @param string $source
     * @return string
     */
    public function deepAiFacialRecognition($source)
    {
        $curl = curl_init();

        $url = 'https://api.deepai.org/api/facial-recognition';

        // upload local texture
        $data = array(
            'image'   => new \CURLFile($source)
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_ENCODING => "",
            CURLOPT_TIMEOUT => 240, //4mins
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SAFE_UPLOAD => false,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "api-key: 0022d160-2e1d-4c8b-a78c-abb83dd9296a",
                "content-type: multipart/form-data"
            ),
        ));

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
//            $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//            $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $message = ' cURL Error #:' . $err.'  url: '.$url.' response: '.$response_body;
            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
        } else {
            if ($httpcode != '200') {
                $message =  ' Http code error #:' . $httpcode.' error: '.$err.' url: '.$url.' response: '.$response_body;
                $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
            } else {
//                $message =  'deepAI response: '.$response_body;
//                $this->logdata($message);
                $json = json_decode($response_body, true);
                if (!empty($json)) {
                    // check if returned error
                    if (isset($json['err'])) {
                        $error = $json->err;
                        $message = 'deepAI error '.$error;
                        $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
                    } else {
                        if(isset($json['output']['faces'])) {
                            $faces = $json['output']['faces'];
                            $faces_coordinates = [];
                            foreach ($faces as $face) {
                                $data = array(
                                    'confidence' => $face['confidence'],
                                    'bounding_box' => $face['bounding_box']
                                );
                                array_push( $faces_coordinates, $data);
                            }
                            return $faces_coordinates;
                        } else {
                            $message =  'no faces found';
                            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 0);
                        }
                    }
                }
            }
        }
        return '';
    }

    /**
     * PHP's curl extension won't let you pass in strings as multipart file upload bodies; you
     * have to direct it at an existing file (either with deprecated @ syntax or the CURLFile
     * type). You can use php://temp to get around this for one file, but if you want to upload
     * multiple files then you've got a bit more work.
     *
     * This function manually constructs the multipart request body from strings and injects it
     * into the supplied curl handle, with no need to touch the file system.
     *
     * @param $ch resource curl handle
     * @param $boundary string a unique string to use for the each multipart boundary
     * @param $fields string[] fields to be sent as fields rather than files, as key-value pairs
     * @param $files string[] fields to be sent as files, as key-value pairs
     * @return resource the curl handle with request body, and content type set
     * @see http://stackoverflow.com/a/3086055/2476827 was what I used as the basis for this
     **/
    function buildMultiPartRequest($ch, $boundary, $fields, $files) {
        $delimiter = '-------------' . $boundary;
        $data = '';
        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . "\r\n"
                . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
                . $content . "\r\n";
        }
        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . "\r\n"
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . "\r\n\r\n"
                . $content . "\r\n";
        }
        $data .= "--" . $delimiter . "--\r\n";
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            ],
            CURLOPT_POSTFIELDS => $data
        ]);
        return $ch;
    }

    /**
     * @param array $faces
     * @return mixed
     */
    public function pickBestFace($faces){
        $best = 0;
        $most_confident = 0;

        foreach ($faces as $key => $face) {
            if ($face['confidence'] > $most_confident) {
                $best = $key;
                $most_confident = $face['confidence'];
            }
        }

        return $faces[$best]['bounding_box'];
    }

    /**
     * @param \Imagick $im
     * @param string $type
     * @throws \ImagickException
     */
    public function distort($im, $type) {
        /** @var \Imagick $im */
        $w1 = $im->getImageHeight();
        $h1 = $im->getImageWidth();
        switch($type) {
            case 'barrelinverse':
                $points = array(
                    //0.2, 0.0, 0.0, 1.0
                    0.2, 0.1, 0.0, 1.0
                );
                $im->setimagebackgroundcolor("#fad888");
                $im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_EDGE);
                $im->distortImage(\Imagick::DISTORTION_BARRELINVERSE, $points, true);
                break;
            case 'depolar':
                $points = array(0);
                $im->setimagebackgroundcolor("#fad888");
                $im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_BACKGROUND);
                $im->distortImage(\Imagick::DISTORTION_DEPOLAR, $points, true);
                $im->scaleImage($h1, $w1);
                break;
            case 'polar':
                $points = array(0);
                $im->setimagebackgroundcolor("#fad888");
                $im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_HORIZONTALTILE);
                $im->distortImage(\Imagick::DISTORTION_POLAR, $points, true);
                break;
            case 'shepards':
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
//            //Move the centre of the image down and to the right
                    50 * $im->getImageWidth() / 100, 50 * $im->getImageHeight() / 100,
                    60 * $im->getImageWidth() / 100, 60 * $im->getImageHeight() / 100,//
//            //Move a point near the top-right of the image down and to the left and down
                    90 * $im->getImageWidth(), 10 * $im->getImageHeight(),
                    80 * $im->getImageWidth(), 15 * $im->getImageHeight(),
                );
                $im->setimagebackgroundcolor("#fad888");
                $im->setImageVirtualPixelMethod(\Imagick::VIRTUALPIXELMETHOD_EDGE);
//                $im->distortImage(\Imagick::DISTORTION_SHEPARDS, $points, true);
                $im->distortImage(\Imagick::DISTORTION_SHEPARDS, $points, true);
                break;
        }

    }

    /**
     * @param string $path
     * @param string $auxpath
     * @param int|string $key
     * @return \Imagick
     * @throws \ImagickException
     */
    public function replaceTransparent($path, $auxpath, $key) {
        try {

            /** @var \Imagick $check */
            $check = new \Imagick($auxpath);

            // Combine frame over previous to circumvent transparency issues
            if ($key > 0) {

                $base_path = $path."original/frame_edited".($key-1).".jpg";
                /** @var \Imagick $base_image */
                $base_image = new \Imagick($base_path);

                // Fully transparent images fuck me up, replace them with previous image, assuming its not the first frame
                $output = $check->identifyFormat("Trim box: %@ number of unique colors: %k");
                // If not completely transparent, use the previous frame as base
                // (If frame to fix is completly transparent, just replace it with previous)
                if ($output !== 'Trim box: 0x0+'.$check->getImageWidth().'+'.$check->getImageHeight().' number of unique colors: 1') {
                    /** @var \Imagick $current_image */
                    $current_image = new \Imagick($path."original/frame_edited".($key).".jpg");

                    $base_image->compositeImage($current_image, \Imagick::COMPOSITE_ATOP, 0, 0);
                    $base_image->setImageFormat('jpg');
                }
            } else {
                $base_image = $check;
            }

            header('Content-Type: image/jpg');
            file_put_contents($auxpath, $base_image);

            return $base_image;
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * @param $_FFMPEG_PATH
     * @param $_FFPROBE_PATH
     */
    public function initFfmpeg($_FFMPEG_PATH, $_FFPROBE_PATH) {
        $ffmpeg = \FFMpeg\FFMpeg::create(array(
            'ffmpeg.binaries'  => $_FFMPEG_PATH,
            'ffprobe.binaries' => $_FFPROBE_PATH,
            'timeout'          => 3600, // The timeout for the underlying process
            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
        ));
        $this->ffmpeg = $ffmpeg;
    }

    /**
     * @param $_FFMPEG_PATH
     * @param $_FFPROBE_PATH
     */
    public function initFfprobe($_FFMPEG_PATH, $_FFPROBE_PATH) {
        $ffprobe = \FFMpeg\FFProbe::create(array(
            'ffmpeg.binaries'  => $_FFMPEG_PATH,
            'ffprobe.binaries' => $_FFPROBE_PATH,
            'timeout'          => 3600, // The timeout for the underlying process
            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
        ));
        $this->ffprobe = $ffprobe;
    }

    /**
     * @param string $gif_path
     * @param array $delays
     */
    public function extractFrames2($gif_path, $delays)
    {
        try {
            $ffmpeg = $this->ffmpeg;

            $base_folder = __DIR__ . '/../resources/frames/';

            /** @var FFMpeg\Media\Video $gif */
            $gif = $ffmpeg->open($gif_path);

            $counter = 0;

            // Sum of all delays is GIF leght
            $limit = array_sum($delays);

            // duration in milliseconds
            $this->logdata('duration: '.$limit);

            for ($s = 0, $i = 0; $s < $limit; $s += $delays[$i], $i++) {
                // Delay is expressed in ticks (hundredths of second)
                $timecode = FFMpeg\Coordinate\TimeCode::fromSeconds($s/100);
                $save_as = $base_folder."original/frame{$counter}.jpg";
                $frame = $gif->frame($timecode);
                $frame->save($save_as);
                if (is_file($save_as)) {
                    $this->logdata(' (s '.$s.')  file: '.$save_as);
                } else {
                    $this->logdata('[FALLO] (s '.$s.')  file: '.$save_as);
                }
                $counter++;
            }

        } catch (\Exception $e) {
            $message = $e->getMessage().' '.$e->getTraceAsString();
            $this->logdata($message, 1);
        }
    }

    /**
     * @param string $gifPath
     * @param string $newGifPath
     * @throws \Exception
     */
    public function liquidRescale2($gifPath, $newGifPath){

        /** FIXME short gifs look too abrupt when transformed */
        /** FIXME The minimum height for a Facebook video is 120 pixels */

        $this->logdata('liquidRescale.. ');

        $original_frames = __DIR__.'/../resources/frames/original/';
        $modified_frames = __DIR__.'/../resources/frames/modified/';
        $this->deleteFrames($modified_frames);
        $this->deleteFrames($original_frames);

        $dt = new DataLogger();

        // since it kept getting PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted
        ini_set('memory_limit', '-1');

        $delays = [];
        $src = new \Imagick($gifPath);
        // Different frames might have different delay
        foreach ($src as $frame) {
            array_push($delays, $src->getImageDelay());
        }
        $message = 'delays: '.implode(', ', $delays);
        $this->logdata($message, 0);

        $this->extractFrames2($gifPath, $delays);

        $framecount = count($delays);
        $this->setRelevantFrames($framecount);

        $new_frames = [];

        $this->logdata('processing '.$framecount.' frames...');
        for ($key = 0; $key < $framecount; $key++) {

            $original_path = $original_frames."frame{$key}.jpg";
            $modified_path = $modified_frames."frame{$key}.jpg";

            array_push($new_frames, $modified_path);

           /** Do stuff */
            try {
                /** @var \Imagick $im */
                $im = new \Imagick($original_path);
                $w1 = $im->getImageHeight();
                $h1 = $im->getImageWidth();

                // zoom to face
                if ($key > $this->KEYFRAME_TWOTHIRDS) {
                    $onlyzoom = 'zoom';
                    $this->ZoomToFaceOnce($im, $key);
                }
//                        $im->addNoiseImage($noiseType);
                if (!empty($onlyzoom)) {
                    $this->logdata("frame {$key} modifying : ".$onlyzoom);
                }

                // Hitting rigidity 0 changes the result drastically, lines get very shagged
                $min = 1;
                /* no scaling => no deformation */
                $delta = mt_rand($min , 25);
                $rigidity = mt_rand($min , 25);
                $condition = $key >= $this->KEYFRAME_PENULTIMATE - 1;
                if ($condition) {
                    $liquidw = round($im->getImageWidth()*0.5);
                    $liquidh = round($im->getImageWidth()*0.5);
//                        $this->distort($im, 'shepards');
                } elseif ($key > $this->KEYFRAME_ANTEPENULTIMATE) {
                    $liquidw = round($im->getImageWidth()*0.5);
                    $liquidh = round($im->getImageWidth()*0.5);
                    $this->logdata("frame {$key} liquid rescaling params liquidw: {$liquidw} liquidh: {$liquidh} delta: {$delta} rigidity: {$rigidity}");
                } else {
                    $liquidw = round($im->getImageWidth()*0.75);
                    $liquidh = round($im->getImageWidth()*0.75);
                    $this->logdata("frame {$key} liquid rescaling params liquidw: {$liquidw} liquidh: {$liquidh} delta: {$delta} rigidity: {$rigidity}");
                }

                // here we liquid rescale them
                $im->liquidRescaleImage($liquidw, $liquidh, $delta, $rigidity);
                $im->scaleImage($h1, $w1);
//                if ($condition || $key > $this->KEYFRAME_ANTEPENULTIMATE) {
//                    $im->scaleImage($im->getImageWidth()*2, $im->getImageWidth()*2);
//                } else {
//                    $im->scaleImage(round($im->getImageWidth()*1.33), round($im->getImageWidth()*1.33));
//                }


                header('Content-Type: image/jpg');
                file_put_contents($modified_path, $im);

            } catch (\Exception $e) {
                $message = ''. $e->getMessage().' '.$e->getTraceAsString();
                $this->logdata($message, 1);
            }
        }

        $finalImage = $this->coalesceImages($new_frames, $delays);
        header("Content-Type: image/gif");
        $finalImage->writeImages($newGifPath, true);

        $dt->logdata('Done');
    }


    /**
     * @param string $imagePaths
     * @param array $delays
     * @return \Imagick
     * @throws \ImagickException
     */
    function coalesceImages($imagePaths, $delays)
    {
        $canvas = new \Imagick();
        foreach ($imagePaths as $key => $imagePath) {
            $canvas->readImage($imagePath);
            $canvas->setImageDelay($delays[$key]);
        }
        $canvas->setImageFormat('gif');

        $finalImage = $canvas->coalesceImages();
        $finalImage->setImageFormat('gif');
        $finalImage->setImageIterations(0); //loop forever
        $finalImage->mergeImageLayers(\Imagick::LAYERMETHOD_COALESCE);

        return $finalImage;
    }

}