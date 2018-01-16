<?php
namespace UseDesk\Facebook\Page\Helpers;

use Carbon\Carbon;
use CompanyEmailChannel;
use Client;
use Ticket;
use TicketComment;
use TicketCommentFile;
use ClientSocialNetwork;
use TicketCommentPost;
use TicketCommentFbPost;
use DB;
use Log;
use UseDesk\Facebook\Page\Post;

class PostHelper extends PageHelper
{

    /** Отправляем комментарий в фейсбук
     * @param TicketComment $ticketComment
     * @param Ticket $ticket
     * @param CompanyEmailChannel $channel
     * @param int|null $parentTicketCommentId
     * @return mixed
     */
    public function sendCommentToFb(TicketComment $ticketComment, Ticket $ticket, CompanyEmailChannel $channel, $parentTicketCommentId)
    {
        $token = $channel->fb_group->token;
        $postId = $ticket->social_id;
        $objId = $this->getObjectId($ticket, $parentTicketCommentId);

        $level = $this->getCommentLevelForOutgoing($postId, $objId);
        $this->addTicketCommentPostModel($ticketComment->id, null, $level, true, $parentTicketCommentId);

        $params = $this->generatePostMessageParams($ticketComment, $ticket);
        $endpoint = $objId . '/comments';

        $request = $this->fb->sendRequest('POST', $endpoint, $params, $token)->getBody();
        $arRequest = json_decode($request, true);
        if (isset($arRequest['id'])) {
            $this->addTicketCommentFbPostModel($ticketComment->id, $ticket->id, $postId, $arRequest['id'], $objId);
            $this->addReaction($parentTicketCommentId);
        }

        return $arRequest;
    }

    /** id объекта (коммент или пост) для отправки в фб
     * @param Ticket $ticket
     * @param $parentTicketCommentId
     * @return null|string
     */
    public function getObjectId(Ticket $ticket, $parentTicketCommentId)
    {
        $objId = null;
        if (intval($parentTicketCommentId)) {
            $objId = $this->getFbIdByTicketCommentId($parentTicketCommentId);
            $parentId = TicketCommentFbPost::getParentId($objId);
            if ($parentId && TicketCommentFbPost::hasParent($parentId)) {
                $objId = $parentId;
            }
        }
        if (!$objId) {
            $objId = $ticket->social_id;
        }

        Log::info($objId);
        return $objId;
    }

    /** Если есть fb_comment_id, отдаем его, если нет, отдаем fb_post_id, т.е id поста в FB
     * @param $ticketCommentId
     * @return null
     */
    private function getFbIdByTicketCommentId($ticketCommentId)
    {
        $objId = null;
        $fbComment = TicketCommentFbPost::select('fb_post_id', 'fb_comment_id')
            ->where('ticket_comment_id', $ticketCommentId)
            ->first();
        if ($fbComment) {
            if ($fbComment->fb_comment_id) {
                $objId = $fbComment->fb_comment_id;
            } elseif ($fbComment->fb_post_id) {
                $objId = $fbComment->fb_post_id;
            }
        }

        return $objId;
    }


    /** Получаем (и создаем, если нужно) клиента для группы в FB
     * @param CompanyEmailChannel $usedeskChannel
     * @return Client
     */
    public function getCreateClientFromChannel(CompanyEmailChannel $usedeskChannel)
    {
        $fbGroup = $usedeskChannel->fb_group;
        $client = $this->getUsedeskClient($fbGroup->fb_id, $usedeskChannel->company_id);
        if (!$client) {

            $client = $this->createClientByFbId($fbGroup->fb_id, $usedeskChannel->name, $usedeskChannel->company_id);
        }

        return $client;
    }

    /** Получаем (и создаем, если нужно) клиента для юзера в FB
     * @param int|string $fbId
     * @param CompanyEmailChannel $usedeskChannel
     * @return Client
     */
    public function getCreateClientByFbId($fbId, $usedeskChannel)
    {
        $client = $this->getUsedeskClient($fbId, $usedeskChannel->company_id);
        if (!$client) {
            $userInfo = $this->getUserInfo($fbId, $usedeskChannel->fb_group->token);
            $client = $this->createClientByFbId($fbId, $userInfo['name'], $usedeskChannel->company_id);
        }

        return $client;
    }

    /** Получаем клиента юздеска по его id в фейсбуке
     * @param int $companyId
     * @param string|int $fbId
     * @return null|Client
     */
    public function getUsedeskClient($fbId, $companyId)
    {
        $client = Client::select('clients.*')
            ->join('client_social_networks', 'client_social_networks.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->where('client_social_networks.uid', $fbId)
            ->where('client_social_networks.type', ClientSocialNetwork::TYPE_FACEBOOK)
            ->first();

        return $client;
    }

    /** Создаем клиента в юздеске на основе его данных из FB
     * @param string|int $fbId
     * @param string $name
     * @param int $companyId
     * @return Client
     */
    public function createClientByFbId($fbId, $name, $companyId)
    {
        $client = Client::create([
            'name' => $name,
            'company_id' => $companyId
        ]);
        $url = 'https://facebook.com/' . $fbId;
        ClientSocialNetwork::create([
            'type' => ClientSocialNetwork::TYPE_FACEBOOK,
            'client_id' => $client->id,
            'uid' => $fbId,
            'url' => $url
        ]);
        return $client;
    }


    /**
     * @param Client $client
     * @param array $value
     * @param CompanyEmailChannel $usedeskChannel
     * @param Carbon $time
     * @return null|Ticket
     */
    public function getCreatePostTicket($client, $value, $usedeskChannel, $time)
    {
        $fbPostId = $value['post_id'];
        $ticket = $this->findPostTicket($fbPostId, $usedeskChannel);
        if (!$ticket) {
            $subject = $this->getTicketSubject($value, $usedeskChannel);
            $ticket = $this->createTicket($client->id, $fbPostId, $usedeskChannel, $time, $subject, Ticket::TICKET_TYPE_POST);
        }
        return $ticket;
    }

    /**
     * @param string $fbPostId
     * @param CompanyEmailChannel $usedeskChannel
     * @return null
     */
    public function findPostTicket($fbPostId, $usedeskChannel)
    {
        return Ticket::where('social_id', $fbPostId)
            ->where('email_channel_id', $usedeskChannel->id)
            ->where('company_id', $usedeskChannel->company_id)
            ->first();
    }

    public function findTicketCommentFbPost($fbCommentId)
    {
        return TicketCommentFbPost::select('ticket_id')
            ->where('fb_comment_id', $fbCommentId)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * @param array $value
     * @param CompanyEmailChannel $usedeskChannel
     * @return string
     */
    private function getTicketSubject($value, $usedeskChannel)
    {
        $fbPostId = $value['post_id'];
        $fbPost = $this->getPostFromFb($fbPostId, $usedeskChannel->fb_group->token);
        if (isset($fbPost['message'])) {
            $subject = substr($fbPost['message'], 0, 100);
        } else {
            $subject = trans('text._facebook.new_post_comment');
        }

        return $subject;
    }

    /** Создаем комментарий в юздеске на основе данных из FB (входящий коммент)
     * @param $ticket
     * @param $client
     * @param $value
     * @param $time
     * @return TicketComment
     */
    public function createPostTicketComment($ticket, $client, $value, $time)
    {
        $this->addImagesToPostMessage($value, $ticket->id, $ticket->company_id);

        $ticketComment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'message' => isset($value['message']) ? $value['message'] : '',
            'client_id' => $client->id,
            'from' => TicketComment::FROM_CLIENT,
            'published_at' => $time,
            'is_html' => 1
        ]);

        $level = $this->getCommentLevelByItem($value['item']);
        $this->addTicketCommentPostModel($ticketComment->id, $value['sender_id'], $level);

        $fbCommentId = (isset($value['comment_id'])) ? $value['comment_id'] : null;
        $fbParentId = (isset($value['parent_id'])) ? $value['parent_id'] : null;
        $this->addTicketCommentFbPostModel($ticketComment->id, $ticket->id, $value['post_id'], $fbCommentId, $fbParentId);

        return $ticketComment;
    }

    /** Получаем уровень комментария (0 - сам пост, 1 - коммент)
     * @param string $item
     * @return int
     */
    private function getCommentLevelByItem($item)
    {
        if ($item == Post::ITEM_COMMENT) return 1;
        return 0;
    }

    /** Получаем уровень для исходящего комментария (1 - ответ на пост, 2 - ответ на коммент)
     * @param string $fbPostId
     * @param string $objId
     * @return int
     */
    public function getCommentLevelForOutgoing($fbPostId, $objId)
    {
        //if ($fbPostId == $objId) return 1;
        return 2;
    }

    /** Добавляем изображения, прикрепленные к комментарию в фб, в текст коммента в юздеске
     * @param array $value
     * @param int $ticketId
     * @param int $companyId
     */
    public function addImagesToPostMessage(&$value, $ticketId, $companyId)
    {
        $urls = [];

        // Фото в комменте
        if (isset($value['photo'])) {
            $urls[] = $value['photo'];
        }

        // Пост с несколькими картинками
        if (isset($value['photos'])) {
            foreach ($value['photos'] as $url) {
                $urls[] = $url;
            }
        }

        // Пост с одной картинкой и без текста
        if (isset($value['photo_id']) && isset($value['link'])) {
            $urls[] = $value['link'];

        }

        foreach ($urls as $url) {
            $this->downloadAndSaveImageFromFb($value, $ticketId, $companyId, $url);
        }
    }

    /** Качаем изображение с сервера фб и сохраняем в юздеске
     * @param array $value
     * @param int $ticketId
     * @param int $companyId
     * @param string $url
     */
    private function downloadAndSaveImageFromFb(&$value, $ticketId, $companyId, $url)
    {
        $udLink = 'upload/gimages/' . $companyId . '/' . $ticketId;
        $path = public_path($udLink);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!isset($value['message'])) $value['message'] = '';
        $filePath = $this->saveFileToPath($path, $url);
        if ($filePath !== false) {
            $value['message'] .= '<p>' . $this->createImgLinkFromPath($udLink, $filePath) . '</p>';
        }
    }

    /** Добавляем модель TicketCommentPost
     * @param int $ticketCommentId
     * @param string|int $fbClientId
     * @param int $level
     * @param bool $hasReply
     * @param int $parentTicketCommentId
     */
    public function addTicketCommentPostModel($ticketCommentId, $fbClientId, $level = 0, $hasReply = false, $parentTicketCommentId = null)
    {
        $params = [
            'ticket_comment_id' => $ticketCommentId,
            'level' => $level,
            'external_client_id' => $fbClientId,
            'has_reply' => $hasReply,
            'parent_ticket_comment_id' => $parentTicketCommentId,
        ];
        TicketCommentPost::create($params);
    }


    /** Добавляем модель TicketCommentFbPost
     * @param int $ticketCommentId
     * @param int $ticketId
     * @param string $fbPostId
     * @param null|string $fbCommentId
     * @param null|string $fbParentId
     */
    public function addTicketCommentFbPostModel($ticketCommentId, $ticketId, $fbPostId, $fbCommentId = null, $fbParentId = null)
    {
        $params = [
            'ticket_id' => $ticketId,
            'ticket_comment_id' => $ticketCommentId,
            'fb_comment_id' => $fbCommentId,
            'fb_post_id' => $fbPostId,
            'fb_parent_id' => $fbParentId,
        ];
        TicketCommentFbPost::create($params);
    }


    /** Проверяем, является ли отправителем коммента группа в ФБ (т.е канал в юздеске)
     * @param $fbSenderId
     * @param CompanyEmailChannel $usedeskChannel
     * @return bool
     */
    public function isUsedeskChannel($fbSenderId, $usedeskChannel)
    {
        return $fbSenderId == $usedeskChannel->fb_group->fb_id;
    }

    /** Проверяем, существует ли уже комментарий в системе
     * @param $fbPostId
     * @param $fbCommentId
     * @return bool
     */
    public function commentExists($fbPostId, $fbCommentId)
    {
        return \TicketCommentFbPost::where('fb_post_id', $fbPostId)
            ->where('fb_comment_id', $fbCommentId)
            ->exists();
    }

    /** Проверяем, существует ли уже пост в системе
     * @param $fbPostId
     * @param $channelId
     * @return bool
     */
    public function postExists($fbPostId, $channelId)
    {
        return Ticket::where('channel', Ticket::CHANNEL_FB)
            ->where('social_id', $fbPostId)
            ->where('email_channel_id', $channelId)
            ->exists();
    }


    /** Генерируем массив, необходимый для отправки исходящего коммента в FB
     * @param TicketComment $ticketComment
     * @param Ticket $ticket
     * @return array
     */
    private function generatePostMessageParams($ticketComment, $ticket)
    {
        $params = [
            'message' => $this->prepareMessage($ticketComment->message)
        ];
        $image = $this->prepareImage($ticketComment);
        if ($image) {
            $params['attachment_url'] = $image->getPublicDownloadLink($ticket->company_id, $ticket->id, $image->ticket_comment_id);
        }

        return $params;
    }

    /** Проверяем, является ли прикрепленный файл изображением (другие типы нельзя прикреплять к комментам в фб), и если да,
     * то возвращаем модель TicketCommentFile
     * @param TicketComment $ticketComment
     * @return null|TicketCommentFile
     */
    private function prepareImage(TicketComment $ticketComment)
    {
        $file = TicketCommentFile::where('ticket_comment_id', $ticketComment->id)->first();
        if ($file && $file->isImage()) return $file;
        return null;
    }


    /** Очищаем сообщение от тегов (для исходящих)
     * @param $message
     * @return mixed|string
     */
    private function prepareMessage($message)
    {
        $message = str_replace("</p><p", "</p>\n<p", $message);
        $message = preg_replace('/<a(.*)(href="(.*)")(.*)<\/a>/U', '$3 ', $message);
        $message = html_entity_decode(trim(strip_tags($message)));

        return $message;
    }

    private function addReaction($commentId)
    {
        TicketCommentPost::reply($commentId);
    }

    private function getFbPostId($fbCommentId) {
        return TicketCommentFbPost::getPostId($fbCommentId);
    }

    private function getPostFromFb($fbId, $token) {
        return $this->get($fbId, $token);
    }


}