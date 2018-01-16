<?php
namespace FbExtention\services\webhook;


use Carbon\Carbon;
use FbRequestLog;
use UseDesk\Facebook\Page\PageController;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class WebhookController
{
    const OBJECT_PAGE = 'page';
    const HUB_SUBSCRIBE = 'subscribe';

    /**
     * @var array
     */
    private $data = [];
    /**
     * @var PageController
     */
    private $page;
    /**
     * @var FbRequestLog
     */
    private $dbLog;
    /**
     * @var Logger;
     */
    private $fileLogger;

    /**
     * WebhookController constructor.
     * @param array $input
     */
    public function __construct($input)
    {
        $this->data = $input;
        $this->createLog($input);
        $this->createFileLogger();
        $this->newFileLog($input);
        $this->createPage();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function parseMessage($data = [])
    {
        if (!$data && $this->data) {
            $data = $this->data;
        }
        if (!$data) return '';

        if (isset($data['hub_mode'])) {
            return $this->parseHub($data);
        }

        if (isset($data['object'])) {
            return $this->parseObject($data);
        }

        return '';
    }

    private function parseHub($data)
    {
        switch ($data['hub_mode']) {
            case static::HUB_SUBSCRIBE:
                return $this->checkSubscription($data);
                break;
            default:
                return '';
        }
    }

    private function parseObject($data)
    {
        switch ($data['object']) {
            case static::OBJECT_PAGE:
                return $this->page->parseData($data);
                break;
            default:
                return '';
        }
    }

    /** Передаем массив с запросом и создаем запись в БД.
     * @param array $input
     */
    private function createLog($input)
    {
        $this->dbLog = FbRequestLog::create([
            'request' => serialize($input),
            'created_at' => Carbon::now(),
            'success' => 0
        ]);
    }

    /**
     * Создаем логгер
     */
    private function createFileLogger()
    {
        $this->fileLogger = new Logger('FB WEBHOOK');
        $this->fileLogger->pushHandler(new StreamHandler(storage_path() . '/logs/fb_webhook.log', Logger::INFO));
    }

    /** Добавляем лог в файл
     * @param array $data
     */
    private function newFileLog($data)
    {
        $this->fileLogger->addNotice('NEW WEBHOOK', $data);
    }

    private function createPage()
    {
        $this->page = new PageController();
    }

    private function checkSubscription($data)
    {
        if (isset($_ENV['services.facebook.fb_verify_token'])
            && $data['hub_verify_token'] == $_ENV['services.facebook.fb_verify_token']) {
            return $data['hub_challenge'];
        }

        return '';
    }
}