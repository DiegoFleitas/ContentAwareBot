<?php
/**
 * Created by PhpStorm.
 * User: Diego
 * Date: 1/19/2019
 * Time: 11:47 PM
 */

namespace ContentAwareBot;

use Stringy\Stringy as S;

class FacebookHelper extends DataLogger
{

    /**
     * @param string $_APP_ID
     * @param string $_APP_SECRET
     * @param string $_ACCESS_TOKEN_DEBUG
     * @return \Facebook\Facebook
     */
    public function init($_APP_ID, $_APP_SECRET, $_ACCESS_TOKEN_DEBUG)
    {
        try {
            # v5 with default access token fallback
            $fb = new \Facebook\Facebook([
                'app_id' => $_APP_ID,
                'app_secret' => $_APP_SECRET,
                'default_graph_version' => 'v3.2',
            ]);
            $fb->setDefaultAccessToken($_ACCESS_TOKEN_DEBUG);
            return $fb;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $message = 'Facebook SDK returned an error: ' . $e->getMessage();
            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
        }
        $message = 'something when wrong at initializing Facebook object';
        $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
    }

    /**
     * @param \Facebook\Facebook $fb
     * @param string $IMAGE_PATH
     * @param string $POST_TITLE
     * @param string $POST_COMMENT
     * @param string $COMMENT
     */
    public function newPost($fb, $IMAGE_PATH, $POST_TITLE, $POST_COMMENT, $COMMENT)
    {

        try {
            $fbfile = $fb->fileToUpload($IMAGE_PATH);

            # fileToUpload works with remote and local images
            $data = array(
                'source' => $fbfile,
                'message' => $POST_TITLE
            );

            /** @var $response \Facebook\FacebookResponse */
            $response = $fb->post('/me/photos', $data);

            /** @var $graphNode \Facebook\GraphNodes\GraphNode */
            $graphNode = $response->getGraphNode();
            $post_id = $graphNode->getField('id');

            // Post created gif
            $this->postCommentToReference($fb, $post_id, $POST_COMMENT);
            // Post original gif
            $this->postCommentToReference($fb, $post_id, $COMMENT);

            // Close stream so we are able to unlink the image later
            $fbfile->close();

            // Move image to avoid posting it again
            // Formatted this way so files get sorted correctly
//            copy($IMAGE_PATH, __DIR__.'/../debug/posted/'.date("Y-m-d H_i_s").'.jpg');
//            if (unlink($IMAGE_PATH)) {
//                $this->logdata('the file was copied and deleted.');
//            } else {
//                $this->logdata('the file couldn\'t deleted.');
//            }
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $message = 'Facebook SDK returned an error: ' . $e->getMessage();
            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
        }
    }

    /**
     * @param \Facebook\Facebook $fb
     * @return string
     */
    public function getLastPost($fb)
    {

        try {

            /** @var $response \Facebook\FacebookResponse */
            $response = $fb->get(
                '/me/feed'
            );

            /** @var $graphEdge \Facebook\GraphNodes\GraphEdge */
            $graphEdge = $response->getGraphEdge();
//            var_dump($graphEdge->asArray());

            /** @var $graphNode \Facebook\GraphNodes\GraphNode */
            foreach ($graphEdge as $graphNode) {
                // avoid polls
                $story = $graphNode->getField('story');
                if (strpos($story, 'poll') === false) {
                    return $graphNode->getField('id');
                }
            }

            $message = 'No valid post found.';
            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $message = 'Facebook SDK returned an error: ' . $e->getMessage();
            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
        }
        return '';
    }

    /**
     * @param \Facebook\Facebook $fb
     * @return array
     */
    public function firstCommandFromLastPost($fb)
    {
        $post = $this->getLastPost($fb);

        $res = $this->getFirstComment($fb, $post, false, true);
        if (!empty($res)) {
            //FILTER_SANITIZE_STRING: Strip tags, optionally strip or encode special characters.
            //FILTER_FLAG_STRIP_LOW: strips bytes in the input that have a numerical value <32, most notably null bytes and other control characters such as the ASCII bell.
            //FILTER_FLAG_STRIP_HIGH: strips bytes in the input that have a numerical value >127. In almost every encoding, those bytes represent non-ASCII characters such as ä, ¿, 堆 etc
            $safe_comment = filter_var($res['text'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

            $res['text'] = strtolower($safe_comment);
            return $res;
        } else {
            return [];
        }
    }

    /**
     * @desc Posts to reference, if reference not given posts to page's feed.
     *
     * @param \Facebook\Facebook $fb
     * @param string $id_reference
     * @param string $comment
     * @param string $comment_photo
     *
     * @return \Facebook\FacebookResponse
     */

    public function postToReference($fb, $id_reference, $comment, $comment_photo = '')
    {
        $this->logdata('postToReference.. ');

        try {

            // setting up the data we'll send
            $data = array();

            if (!empty($comment)) {
                $data['message'] = $comment;
            }
            if (!empty($comment_photo)) {
                // Supports images hosted locally or remotely
                $data['source'] = $fb->fileToUpload($comment_photo);

            }

            if (count($data) < 1) {
                echo 'No data given to post.';
            }

            /** @var $response \Facebook\FacebookResponse */
            /* Since we provided a 'default_access_token', we don't need to pass it as second parameter here. */
            if (!empty($id_reference)) {
                // $id_reference Could either be a post or a comment
                $response = $fb->post($id_reference . '/comments', $data);
            } else {
                // post to page feed
                if (empty($comment_photo)) {
                    $response = $fb->post('me/feed', $data);
                } else {
                    $response = $fb->post('me/photos', $data);
                }
            }
            return $response;

        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $message = 'Facebook SDK returned an error: ' . $e->getMessage();
            $this->logdata($message, 1);
        }
    }

    /**
     * @param \Facebook\Facebook $fb
     * @param string $ID_REFERENCE
     * @param string $COMMENT
     * @param string $COMMENT_PHOTO
     */
    public function postCommentToReference($fb, $ID_REFERENCE, $COMMENT, $COMMENT_PHOTO = '')
    {
        try {
            $data = array ();

            if (!empty($COMMENT)) {
                $data['message'] = $COMMENT;
            }

            if (!empty($COMMENT_PHOTO)) {
                $data['source'] = $fb->fileToUpload($COMMENT_PHOTO);
            }

            // $ID_REFERENCE Could either be a post or a comment
            /** @var $response \Facebook\FacebookResponse */
            $response = $fb->post($ID_REFERENCE.'/comments', $data);
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $message = 'Facebook SDK returned an error: ' . $e->getMessage();
            $this->logdata('['.__METHOD__.' ERROR] '.__FILE__.':'.__LINE__.' '.$message, 1);
        }
    }

    /**
     * @param \Facebook\Facebook $fb
     * @param string $path
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function postVideo($fb, $path, $title, $description){

        $this->logdata('posting video..');

        $data = [
            'title' => $title,
            'description' => '',
        ];

        try {
            /** @var $response array */
//            $response = $fb->uploadVideo('me', $path, $data);
            $response = $fb->uploadVideo('me', $path, $data);


            if (!empty($response['video_id'])) {

                $comment['video_id'] = '';

                /** Facebook takes it's time to post the video so to comment on it we just gotta wait really */
                $seconds = 60;
                sleep($seconds);

                $post_id = $this->getLastPost($fb);
                if (!empty($post_id)) {
//                    $data['description'] = $description;
//                    $original = 'C:\Users\Diego\PhpstormProjects\ContentAwareBot\src\resources\gifs\gif.gif';
//                    $comment = $fb->uploadVideo($post_id, $original, $data);

                    $POST_COMMENT = $description;
                    $this->postCommentToReference($fb, $post_id, $POST_COMMENT);
                }

                if (!empty($comment['video_id'])) {
                    $this->logdata('success to comment');
                } else {
                    $this->logdata('failed to comment');
                }
            }

        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $message =  'Graph returned an error: ' . $e->getMessage();
            $this->logdata($message, 1);
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $message =  'Facebook SDK returned an error: ' . $e->getMessage();
            $this->logdata($message, 1);
        }
        $message =  'Video ID: ' . $response['video_id'];
        $this->logdata($message, 1);

    }

}
