<?php
namespace UseDesk\Facebook;

use Carbon\Carbon;

class FbController
{
    const ATTACHMENT_IMAGE = 'image';
    const ATTACHMENT_FILE = 'file';

    /**
     * @var \Facebook\Facebook
     */
    protected $fb;
    /**
     * @var Carbon
     */
    protected $now;

    public function __construct()
    {
        $this->fb = new \Facebook\Facebook([
            'app_id' => $_ENV['services.facebook.id'],
            'app_secret' => $_ENV['services.facebook.secret'],
            'default_graph_version' => 'v2.10',
        ]);
        $this->now = Carbon::now();
    }
}