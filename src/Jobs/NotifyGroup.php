<?php

namespace Fpaipl\Brandy\Jobs;

use Illuminate\Bus\Queueable;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Fpaipl\Panel\Events\PushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Fpaipl\Panel\Notifications\WebPushNotification;

/**
 * The NotifyGroup job is responsible for sending notifications to users in a group.
 * It can send both web push notifications and broadcast events to notify users.
 *
 * Implements ShouldQueue for asynchronous processing and ShouldBeUnique to prevent duplicate jobs.
 */
class NotifyGroup implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ledgerId;
    protected $user;
    protected $skipId;
    protected $event;
    protected $title;
    protected $message;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @param mixed  $webPushChannel Channel for web push notifications.
     * @param string $pushChannel    Channel for push notifications.
     * @param string $event          Event name for the push notification.
     * @param string $title          Title of the notification.
     * @param string $message        Message body of the notification.
     * @param string $action         Action associated with the notification.
     */
    public function __construct(
        $ledgerId, $skipId, $event, $title, $message, $action)
    {
        $this->skipId = $skipId;
        $this->ledgerId = $ledgerId;
        $this->event = $event;
        $this->title = $title;
        $this->message = $message;
        $this->action = $action;
    }

    public function handle()
    {
        $ledger = Ledger::find($this->ledgerId);
        if (!$ledger) {
            return;
        }
        $ledgerNotificationMembers = $ledger->notigroups;
        foreach ($ledgerNotificationMembers as $ledgerNotificationMember) {
            if($ledgerNotificationMember->user->uuid != $this->skipId){
                $this->send($ledgerNotificationMember->user);
            }
        }
    }

    public function send($user)
    {
        Log::info('send to uuid: ' . $user->uuid);
        $user->notify(new WebPushNotification($this->title, $this->message, $this->action));
        PushNotification::dispatch($user->uuid, $this->event, $this->title, $this->message);
    }
}
