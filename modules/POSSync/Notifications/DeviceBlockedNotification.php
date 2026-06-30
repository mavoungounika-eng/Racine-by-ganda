<?php

namespace Modules\POSSync\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\POSSync\Models\PosDevice;

class DeviceBlockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PosDevice $device,
        private string $reason
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('POS Device Blocked')
            ->line('A POS device has been blocked after a security event.')
            ->line('Machine ID: ' . $this->device->machine_id)
            ->line('Device name: ' . $this->device->name)
            ->line('Reason: ' . $this->reason)
            ->line('Blocked at: ' . now()->toDateTimeString());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'machine_id' => $this->device->machine_id,
            'device_name' => $this->device->name,
            'reason' => $this->reason,
            'blocked_at' => now()->toIso8601String(),
        ];
    }
}
