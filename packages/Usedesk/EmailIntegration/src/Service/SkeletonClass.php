<?php

namespace Usedesk\EmailIntegration\Service;

use Usedesk\EmailIntegration\Exceptions\NameException;

use Webklex\IMAP\Client;

class SkeletonClass
{
    protected $config;

    /**
     * Create a new Skeleton Instance
     */
    public function __construct()
    {
        $this->config = [
            'host'          => 'somehost.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => 'username',
            'password'      => 'password',
        ];
        
        $aMailboxes = $this->connect();

        $this->getEmails($aMailboxes);
    }
    /**
     * Connect to the IMAP Server
     *
     * @param string $phrase Phrase to return
     *
     * @return string Returns the phrase passed in
     */
    public function connect()
    {
        $oClient = new Client($this->config);

        $oClient->connect();

        //Get all Mailboxes
        return $aMailboxes = $oClient->getFolders();

    }
    /**
     * Loop through every Mailbox
     *
     * @param \Webklex\IMAP\Folder $aMailboxes
     *
     * @return string Returns the phrase passed in
     */
    public function getEmails($aMailboxes)
    {
        foreach($aMailboxes as $oMailbox){
            //Get all Messages of the current Mailbox
            /** @var \Webklex\IMAP\Message $oMessage */
            foreach($oMailbox->getMessages() as $oMessage){
                echo $oMessage->subject.'<br />';
                echo 'Attachments: '.$oMessage->getAttachments()->count().'<br />';
                echo $oMessage->getHTMLBody(true);
                
                //Move the current Message to 'INBOX.read'
                try{
                    $result = $oMessage->moveToFolder('INBOX.read');
                    $result == true
                    echo 'Message has ben moved';
                }catch(Exception $e){
                     throw new NameException($e);
                }
            }
        }
    }
}
