<?php
namespace UseDesk\Facebook;


use Ticket;
use TicketComment;
use TicketCommentFile;
use CompanyEmailChannel;

class FbMessengerController extends FbController
{
    /**
     * @param TicketComment $ticketComment
     * @param Ticket $ticket
     * @param CompanyEmailChannel $channel
     * @return array
     */
    public function sendMessageFromPageToUser(TicketComment $ticketComment, Ticket $ticket, CompanyEmailChannel $channel)
    {
        $request = [];
        $token = $channel->fb_group->token;
        $psid = $ticket->social_id;

        $params = $this->generateMessageParams($psid, $ticketComment->message);
        $endpoint = 'me/messages';

        if ($params) {
            $request = $this->fb->sendRequest('POST', $endpoint, $params, $token)->getBody();
        }

        $this->sendFiles($psid, $ticketComment, $token, $ticket->company_id);

        if($request) return json_decode($request, true);
        return [];
    }

    private function sendFiles($psid, TicketComment $ticketComment, $token, $companyId)
    {
        $endpoint = 'me/messages';
        $files = $ticketComment->files;

        foreach ($files as $file) {
            $params = $this->generateFileParams($psid, $file, $companyId, $ticketComment->ticket_id);
            $this->fb->sendRequest('POST', $endpoint, $params, $token)->getBody();
        }
    }

    /**
     * @param $psid
     * @param $message
     * @return array
     */
    private function generateMessageParams($psid, $message)
    {
        $params = [];
        $text = $this->prepareMessage($message);
        if (!$this->messageIsEmpty($text)) {
            $params = [
                'recipient' => [
                    'id' => $psid
                ],
                'message' => [
                    'text' => $this->prepareMessage($message)
                ]
            ];
        }

        return $params;
    }


    private function generateFileParams($psid, TicketCommentFile $file, $companyId, $ticketId)
    {
        $params = [
            'recipient' => [
                'id' => $psid
            ],
            'message' => [
                'attachment' => [
                    'type' => ($file->isImage()) ? self::ATTACHMENT_IMAGE : self::ATTACHMENT_FILE,
                    'payload' => [
                        'url' => $file->getPublicDownloadLink($companyId, $ticketId, $file->ticket_comment_id)
                    ]
                ]
            ]
        ];

        return $params;
    }

    /**
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

    private function messageIsEmpty($message)
    {
        return strlen($message) == 0;
    }
}