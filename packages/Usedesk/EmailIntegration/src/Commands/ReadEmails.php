<?php

namespace Usedesk\EmailIntegration\Commands;

use Illuminate\Console\Command;
use Usedesk\EmailIntegration\Contracts\Permission;

/**
 * Сохраняет сообщения для соединений через IMAP
 */
class ReadEmails extends Command
{
    protected $signature = 'permission:create-permission 
                {name : The name of the permission} 
                {guard? : The name of the guard}';

    protected $name = 'usedesk:read';

    protected $description = 'Read company channels';

    public function handle()
    {
        $permissionClass = app(Permission::class);
        $permission = $permissionClass::create([
            'name' => $this->argument('name'),
            'guard_name' => $this->argument('guard'),
        ]);
        $this->info("Permission `{$permission->name}` created");
    }




     public function fire() {
        try{
            foreach (Company::all() as $company) 
                {
                $emailChannels = $company->emailChannels()->where('has_problem', '=', false)->get();

                foreach ($emailChannels as $emailChannel) {


                    try{
                        if ($emailChannel->incoming_connection == CompanyEmailChannel::CONNECTION_EXTERNAL) {

                            $imap = new \UseDesk\Email\ImapConnection($emailChannel->imap_host, $emailChannel->imap_port,
                                $emailChannel->imap_username, Crypt::decrypt($emailChannel->imap_password) );

                            $messages = $imap->customRead();

                            if ($messages !== false) {
                                foreach ($messages as $message) {
                                    //сохранение сообщения
                                    $emailChannel->readMessage($message);
                                }

                            } else {

                                $emailChannel->has_problem = true;

                                $emailChannel->save();

                            }
                        }
                    }
                    catch(Exception $e){
                        continue;
                    }
                }
            }
        }
        catch(Exception $e){
        }
    }
}