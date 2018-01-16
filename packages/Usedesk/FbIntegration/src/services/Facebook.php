<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 03.08.15
 * Time: 15:14
 */

namespace FbExtention;


use Carbon\Carbon;
use Facebook\Authentication\AccessToken;
use Facebook\FacebookRequest;

class Facebook
{
    protected $fb;

    public function __construct() {
        $this->fb = new \Facebook\Facebook([
            'app_id' => $_ENV['services.facebook.id'],
            'app_secret' => $_ENV['services.facebook.secret'],
            'default_graph_version' => 'v2.10',
        ]);
    }

    public function MonitoringSetWord($words){
        $query = '';
        for ($i = 0; $i < count($words); $i++) {
            $query .= 'word['.$i.']='.urlencode($words[$i]).'&';
        }
        return json_decode(file_get_contents($_ENV['service.facebook.monitor'].'add/?'.$query), true);
    }

    public function MonitoringGetWord($word, $start, $end){
        return json_decode(file_get_contents($_ENV['service.facebook.monitor'].'get/?word='.urlencode($word).'&start='.$start.'&end='.$end), true);
    }

    private function fbReq($method, $type, $params = array(), $token) {
        if (!strcasecmp($type, 'GET')) {
            $request = new FacebookRequest($this->fb->getApp(),
                $token,
                $type,
                '/' . $method . '?' . http_build_query($params)
            );
        } elseif (!strcasecmp($type, 'POST')) {
            $request = new FacebookRequest($this->fb->getApp(),
                $token,
                $type,
                '/' . $method,
                $params
            );
        } else return false;
        $response = $this->fb->getClient()->sendRequest($request);
        return json_decode($response->getBody(), true);
    }

    public function getAvatar($uid, $token) {
        $resp = $this->fbReq($uid, 'GET', array('fields' => 'picture'), $token);
        return $resp['picture']['data']['url'];
    }

    public function getUser($user_id, $token) {
        $resp = $this->fbReq($user_id, 'GET',['fields' => 'name,link,picture'], $token);
        return $resp;
    }

    public function getPageFeed($pid, $token, $since) {
        try {
            $resp = array();
            $since = Carbon::parse($since);
            $url = '/' . $pid . '/feed?fields=comments{attachment,created_time,from,message,comments{attachment,created_time,from,message}},created_time,id,updated_time,message,child_attachments,link,picture,name,description,from,to&limit=100&since='.$since->timestamp;
            $request = new FacebookRequest($this->fb->getApp(),
                $token,
                'GET',
                $url
            );
            $response = $this->fb->getClient()->sendRequest($request);
            $data = json_decode($response->getBody(), true)['data'];
            $resp = array_merge($resp, $data);
            return $resp;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function sendMsg($id, $msg, $token) {
        return $this->fbReq($id.'/messages', 'POST', array('message' => $msg),$token);
    }

    public function sendComment($id, $msg, $token) {
        return $this->fbReq($id.'/comments', 'POST', array('message' => $msg), $token);
    }

    public function getUpdates($resp, $updated_time, $interval, $fb_id) {
        $new = array();
        $lastUpdate = Carbon::parse($updated_time)->getTimestamp();
        foreach ($resp as $post) {
            if ($post['from']['id'] == $fb_id) continue;
            $time = Carbon::parse($post['updated_time'])->getTimestamp();
            if ($lastUpdate > $time && $time < ($time-$interval)) continue;
            $new[] = $post;
        }
        return $new;
    }

    public function getNewDialogs($pid, $token, $updated_time, $url = null, $new = array()) {
        $url = is_null($url) ? $pid.'/conversations' : $url;
        $params = ['fields' => 'updated_time'];
        $resp = $this->fbReq($url, 'GET', $params, $token);
        $lastUpdate = Carbon::parse($updated_time)->getTimestamp();
        foreach ($resp['data'] as $dialog) {
            $time = Carbon::parse($dialog['updated_time'])->getTimestamp();
            if ($lastUpdate > $time) return $new;
            $new[] = $dialog;
        }
        if (!isset($resp['paging']['next']))  return $new;
        $url = substr($resp['paging']['next'], 31);
        $this->getNewDialogs($pid, $token, $updated_time, $url, $new);
        return $new;
    }

    public function getNewMsgs($resp, $updated_time, $token, $fb_id) {
        $lastUpdate = Carbon::parse($updated_time)->getTimestamp();
        $messages = array();
        foreach ($resp as $dialog) {
            $messages[$dialog['id']] = array();
            echo '<pre>';
            var_dump($dialog);
            echo '</pre>';
            $msgs = $this->fbReq($dialog['id'].'/messages', 'GET', array('fields' => 'from, to, message, attachments, created_time'), $token);
            foreach ($msgs['data'] as $msg) {
                if ($msg['from']['id'] == $fb_id) continue;
                $time = Carbon::parse($msg['created_time'])->getTimestamp();
                if ($lastUpdate > $time) continue;
                $messages[$dialog['id']][] = $msg;
            }
        }
        return $messages;
    }

    /*public function getWhereUrl($url, $token) {
        preg_match('/(\/|fbid=)(\d{1,25})(\/|$|&)/', $url, $matches);
        $id = $matches[2];
        if (preg_match('/facebook.com\/(.*?)\//', $url, $matches) && !preg_match('/\/photos\//', $url)) {
            $username = $matches[1];
            $user = $this->fbReq($username, 'GET', [], $token);
            $postId = $user['id'].'_'.$id;
        } elseif (preg_match('/facebook.com\/permalink.php\?/', $url)) {
            $postId = '';
        } else {
            $postId = $id;
        }
        $response = $this->fbReq($postId, 'GET', [], $token);
        return $response;
    }*/
}