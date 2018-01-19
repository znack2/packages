<?php

namespace Usedesk\FbIntegration\Http\Controllers;

class CountController 
{
	private $fb;
	private $accessToken;

	public function __construct($fb, $accessToken) {
		$this->fb = $fb;
		$this->accessToken = $accessToken;
	}


	//your Facebook page username or ID
	public function countLikes($account='') 
	{ 
		 if($account){
			 $url='http://api.facebook.com/method/fql.query?query=SELECT fan_count FROM page WHERE';
			 if(is_numeric($account)) { $qry=' page_id="'.$account.'"';} //If account is a page ID
			 else {$qry=' username="'.$account.'"';} //If account is not a ID. 
			 $xml = @simplexml_load_file($url.$qry) or die ("invalid operation");
			 $fb_count = $xml->page->fan_count;
			 return $fb_count;
		}else{
			return '0';
		}
	}

	public function countReaction($objectID, $reactions) 
	{
		foreach ($reactions as $key => $position) {
	    	$fields[] = "reactions.type({$key}).limit(0).summary(total_count).as({$key})";
		}
		$reactionParams = ['ids' => $objectID, 'fields' => join(',', $fields)];
		$endpoint = '/?' . http_build_query($reactionParams);
		$request = $this->fb->request('GET', $endpoint, [], $this->accessToken);
	    /*
	     * Fetch the reactions count from Facebook
	     */
		try {
	    	$response = $this->fb->getClient()->sendRequest($request);
	    	$reactions = json_decode($response->getBody(), true);
	        $reactions = current($reactions);
		} catch (\Exception $e) {
	    	// die('Error getting reactions: ' . $e);
	    	return [];
		}
	}