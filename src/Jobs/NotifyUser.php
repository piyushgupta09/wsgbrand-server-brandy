<?php

namespace Fpaipl\Brandy\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Fpaipl\Panel\Events\PushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Fpaipl\Panel\Notifications\WebPushNotification;

/**
 * The NotifyUser job is responsible for sending notifications to users.
 * It can send both web push notifications and broadcast events to notify users.
 *
 * Implements ShouldQueue for asynchronous processing and ShouldBeUnique to prevent duplicate jobs.
 */
class NotifyUser implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webPushChannel;
    protected $pushChannel;
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
        $webPushChannelId, $pushChannel, $event, $title, $message, $action)
    {
        $this->webPushChannel = User::where('uuid', $webPushChannelId)->first(); // user uuid
        $this->pushChannel = $pushChannel; // party or employee uuid
        $this->event = $event;
        $this->title = $title;
        $this->message = $message;
        $this->action = $action;
        if (config('app.debug')) {
            Log::info('NotifyUser job created', [
                'webPushChannelId' => $webPushChannelId,
                'pushChannel' => $this->pushChannel,
                'event' => $this->event,
                'title' => $this->title,
                'message' => $this->message,
                'action' => $this->action,
            ]);
        }
    }

    /**
     * Execute the job.
     *
     * Logs the notification details and sends out the notifications.
     */
    public function handle()
    {
        // Notify via the web push channel
        $this->webPushChannel->notify(new WebPushNotification($this->title, $this->message, $this->action));

        // Broadcast the event
        PushNotification::dispatch($this->pushChannel, $this->event, $this->title, $this->message);
    }
}
