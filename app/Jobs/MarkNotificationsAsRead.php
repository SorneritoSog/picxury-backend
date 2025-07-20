<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MarkNotificationsAsRead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unreadNotificationIds;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Support\Collection $unreadNotificationIds
     */
    public function __construct(Collection $unreadNotificationIds)
    {
        $this->unreadNotificationIds = $unreadNotificationIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->unreadNotificationIds->isNotEmpty()) {
            Notification::whereIn('id', $this->unreadNotificationIds)->update(['is_read' => 1]);
        }
    }
}
