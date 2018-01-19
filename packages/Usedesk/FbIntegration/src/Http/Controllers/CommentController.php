<?php

namespace Usedesk\FbIntegration\Http\Controllers;

class CommentController 
{
    private $fb;
    private $accessToken;

    public function __construct($fb, $accessToken) {
        $this->fb = $fb;
        $this->accessToken = $accessToken;
    }

    /*
	 * Fetch latest comments
	 */
	public function getComments($objectID)
    {
    	$commentParams = ['filter' => 'stream', 'order' => 'reverse_chronological'];
    	$request = $this->fb->request(
        	'GET',
        	"/{$objectID}/comments?" . http_build_query($commentParams),
        	[],
        	$this->accessToken
    	);
    	try {
        	$response = $this->fb->getClient()->sendRequest($request);
        	return json_decode($response->getBody(), true)['data'];
    	} catch (\Exception $e) {
        	// die('Error getting comments: ' . $e);
        	return [];
    	}
	}
	/*
	 * Returns an array of comments that contains a specific keyword
	 */
	public function getCommentsByKeyword($objectID, $keyword, $caseSensitive = false)
    {
        $comments = $this->comments($objectID);
        return array_filter($comments, function ($comment) use ($keyword, $caseSensitive) {
            $message = $comment['message'];
            if ($caseSensitive) {
                return strpos($message, $keyword) > -1;
            } else {
                return strpos(strtolower($message), strtolower($keyword)) > -1;
            }
        });
    }
}CountController