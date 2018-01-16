<?php  

namespace Usedesk\EmailIntegration\Http\Controllers;

class IntegrationController
{

    // public function on_start()
    // {
    //     require $this->getPackagePath() . '/vendor/autoload.php';
    // }

    /**
     * @param array|string $to
     * @param string $subject
     * @param string $body
     * @param int|null $ticketId
     * @param array $quotes
     * @param array $files
     * @param TicketComment|null $ticketComment
     * @throws Exception
     * @return bool
     */
    public function sendMessage($to, $subject, $body, $ticketId = null, array $quotes = [], $files = [], $ticketComment = null, $from = null)
    {

        $user = Auth::user()->user();
        if (!$user && $ticketComment && $ticketComment->user_id) {
            $user = $ticketComment->user;
        }
        $npsMessage = User_IntegrationController::CheckNpsSettings('', 'email', $ticketId);
        $param = [
            'message' => $body,
            'npsMessage' => $npsMessage,
            'signature' => ((!empty($user)) && $user->use_signature) ? $user->my_signature : $this->signature,
            'usedeskSignature' => $this->company->signature_enabled,
            'companyid' => $this->company->id,
            'ticketId' => $ticketId,
            // 'quotes' => $quotes,
        ];
        $signing_domain="usedesk.ru";
        if($this->company->id == 153932){
            $signing_domain = "moneyman.ru";
        }
        else if($this->company->id == 153980){
            $signing_domain = "moneyman.ge";
        }
        else if($this->company->id == 153979){
            $signing_domain = "moneyman.kz";
        }

        if(!$this->editor_quote && $this->quotes == 0){
            $param['quotes'] = $quotes;
        }
        //создание тела сообщения
        $messageText = View::make('user.emails.ticket_comment',$param)->render();


        //получаем адреса Cc и Bcc
        $ticketComment->copy = $ticketComment->copyEmail()->get();


        $ticket = Ticket::select('company_id', 'email_channel_email', 'client_id')->where('id', $ticketComment->ticket_id)->first();

        $client = Client::select('id', 'name')->where('id', $ticket->client_id)->first();

        if ($ticket->email_channel_email) {
            $email = $ticket->email_channel_email;
        } else {
            $email = ClientEmail::where('client_id', $client->id)->first()->email;
        }

        //если присутствую адреса копий
        if ($ticketComment->copy) {

            $cc = [];

            $bcc = [];

            foreach ($ticketComment->copy as $addess) {

                $message['to'][] = ['email' => $addess['email'], 'type' => $addess['type']] ;

                if ($addess['type'] == TicketCommentCopyEmail::TYPE_CC) {

                    $cc[] = $addess['email'];

                    $message['to'][] = ['email' => $addess['email'], 'type' => 'cc'] ;

                } elseif ($addess['type'] == TicketCommentCopyEmail::TYPE_BCC) {

                    $bcc[] = $addess['email'];

                    $message['to'][] = ['email' => $addess['email'], 'type' => 'bcc'] ;

                }
            }

            $ticketComment->cc = implode(',',$cc);

            $ticketComment->bcc = implode(',',$bcc);

        }

        //проверка на внутреннее/внешнее соединение
        switch ($this->outgoing_connection) {
            //внутреннее
            case CompanyEmailChannel::CONNECTION_INTERNAL:

                $recipient_name = "";
                if(isset($client->name)){
                    $recipient_name = $client->name;
                }
                $recivers[] = ['email' => $to,'name'=>$recipient_name, 'type' => 'to'];

                if (!empty($cc)) {

                    foreach ($cc as $cc) {

                        $recivers[] = ['email' => $cc, 'type' => 'cc'];

                    }
                }

                if (!empty($bcc)) {

                    foreach ($bcc as $bcc) {

                        $recivers[] = ['email' => $bcc, 'type' => 'bcc'];

                    }
                }

                if($this->from_name == 'company'){
                    $from_name = $this->company->name;
                }
                else{
                    $from_name = $user->name;
                }
                $message = [
                    'subject' => $subject,
                    'html' => $messageText,
                    'from_email' => $from ? $from : array_keys($this->getFrom())[0],
                    'from_name'=> $from_name,
                    'to' => $recivers,
                    'headers' => ['Reply-To' => array_keys($this->getFrom())[0]],
                ];

                foreach ($files as $file) {

                    if (!preg_match('/(.*?)_(.*?)$/',basename($file), $name)) continue;

                    $message['attachments'][] = [
                        'type' => mime_content_type($file),
                        'name' => $name[2],
                        'content' => base64_encode(file_get_contents($file)),
                    ];

                }

                try {

                    if ($this->type === 'gmail') {

                        $from = \UseDesk\Google\GmailApi::getUsersEmail();

                        $headers = 'From: <'.$from.'>
                            To: <'.$email.'>
                            Subject: =?utf-8?b?'.base64_encode($subject).'?=
                            Date: '.date('r').'
                            Message-ID: <'.$ticketId.'@ticket.usedesk.ru>
                            Content-Type: text/html; charset = utf-8
                            MIME-Version: 1.0';

                        $mime = rtrim(strtr(base64_encode($headers.$messageText), '+/', '-_'), '=');

                        $msg = new Google_Service_Gmail_Message();

                        $msg->setRaw($mime);

                        $message = \UseDesk\Google\GmailApi::sendMessage("me", $msg);


                    } else {

                    //                        $mandrillResult = MandrillFacade::messages()->send($message);
                        $key = 0;
                        switch($ticket->company_id){
                            case 153980:  $key = "MwWYvAqGxIk4O9EVhEOPog"; break;
                            case 154169:  $key = "FtGHvsobhPE75rxKG8iQgQ"; break;
                            case 153932:  $key = "5juta4WuRMA6XTWbFlaeMw"; break;
                            case 153979:  $key = "sb8gWTsRdLIYwzV7AKASiA"; break;
                            case 154532:  $key = "TZ1wOmQF9NpIjKvSjOd95g"; break;
                            case 154365:  $key = "f17Kbix0RNwpgNWo0HPejw"; break;
                            case 154105:  $key = "5juta4WuRMA6XTWbFlaeMw"; break;
                        }
                        if(isset($ticket->email_channel_id) && $ticket->email_channel_id == 837){
                            $key = "7G2nyMpI9EXbSLcnBlsUIQ";
                        }
                        $mandrillResult =  \UseDesk\MandrillChannel\MandrillChannel::sendMandrillMessageWithKey($message,$key);
                        if ($ticketComment) {

                            $ticketComment->by_mandrill = true;

                            $ticketComment->mandrill_id = $mandrillResult[0]['_id'];

                            unset($ticketComment->copy);
                            unset($ticketComment->cc);
                            unset($ticketComment->bcc);

                            $ticketComment->save();

                        }

                    }

                } catch (Exception $e) {

                    Log::error($e);

                    return false;

                }

                return true;

                break;

            //внешнее
            case CompanyEmailChannel::CONNECTION_EXTERNAL:
                try {
                    if($this->type == $this::TYPE_SYNC){
                        $sync_message = DB::table('sync_engine_ticket_comments')->where('ticket_id',$ticket->id)->first();
                        $message_id = 0;
                        if($sync_message){
                            $message_id = $sync_message->sync_engine_id;
                        }
                       $response = \UseDesk\SyncEngine\SyncEngineConnection::sendMessage($this->getFrom(), $this->getOutgoingReplyTo(),
                            $to, $subject, $messageText, $files, $ticketComment->cc, $ticketComment->bcc, $this->sync_engine_id,$message_id);
                        if(isset($response['id'])){
                            DB::table('sync_engine_ticket_comments')->insert([
                                'ticket_id'=>$ticketComment->ticket_id,
                                'comment_id'=>$ticketComment->id,
                                'sync_engine_id'=>$response['id']
                            ]);
                        }
                        if(isset($response['thread_id'])){
                            $thread =   DB::table('sync_engine_tickets')->where('ticket_id',$ticketComment->ticket_id)->first();
                            if($thread) {
                                DB::table('sync_engine_tickets')->where('ticket_id', $ticketComment->ticket_id)->update([
                                    'thread_id' => $response['thread_id'],
                                ]);
                            }
                            else{
                                DB::table('sync_engine_tickets')->insert([
                                    'ticket_id'=>$ticketComment->ticket_id,
                                    'thread_id' => $response['thread_id']
                                ]);
                            }
                        }
                        return $response;
                    }
                    else{
                        $smtp = new SmtpConnection($this->smtp_host, $this->smtp_port,
                            $this->smtp_username, Crypt::decrypt($this->smtp_password), $this->unencrypted_connection, $this->smtp_encrypted);

                        $response = $smtp->sendMessage($this->getFrom(), $this->getOutgoingReplyTo(),
                            $to, $subject, $messageText, $files, $ticketComment->cc, $ticketComment->bcc);
                        if (!$response) {
                            self::hasProblem($this, 'SMTP');
                            TicketComment::where('id',$ticketComment->id)->update([
                                'type'=>TicketComment::TYPE_PRIVATE
                            ]);
                        }
                        return $response;
                    }

                }
                catch(Exception $e){
                    Log::alert($e);
                    self::hasProblem($this, 'SMTP');
                    TicketComment::where('id',$ticketComment->id)->update([
                        'type'=>TicketComment::TYPE_PRIVATE
                    ]);
                    return false;
                }
                break;

            default:

                return false;

        }
    }
}
