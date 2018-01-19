<?php

namespace Usedesk\FbIntegration\Http\Controllers;

class MediaController 
{
    private $fb;
    private $accessToken;

    public function __construct($fb, $accessToken) {
        $this->fb = $fb;
        $this->accessToken = $accessToken;
    }

    /*
     * Downloads and saves public profile image
     */
    public function saveProfileImage($uid, $width, $height, $filename)
    {
        $profileImageParams = [
            'width' => $width,
            'height' => $height,
        ];
        $endpoint = "http://graph.facebook.com/{$uid}/picture?" . http_build_query($profileImageParams);
        copy($endpoint, $filename);
    }
}
