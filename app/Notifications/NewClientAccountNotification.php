<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewClientAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $client;
    protected $vendor;
    protected $temporaryPassword;
    public $tries;
    public $timeout;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $client, Vendor $vendor, $temporaryPassword = null)
    {
        $this->client = $client;
        $this->vendor = $vendor;
        $this->temporaryPassword = $temporaryPassword;

        // Get values from config
        $this->tries = config('notifications.tries', 3);
        $this->timeout = config('notifications.timeout', 30);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Assign specific queue for mail notifications.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => config('notifications.queue.emails', 'emails'),
        ];
    }

    /**
     * Define backoff intervals for retries.
     */
    public function backoff(): array
    {
        return config('notifications.backoff', [10, 30, 60]);
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'Our Platform');
        $supportEmail = config('mail.support_email', 'support@example.com');

        return (new MailMessage)
            ->subject('Welcome to ' . $this->vendor->business_name . ' - Client Account Created')
            ->markdown('emails.client-welcome', [
                'client' => $this->client,
                'vendor' => $this->vendor,
                'temporaryPassword' => $this->temporaryPassword,
                'appName' => $appName,
                'supportEmail' => $supportEmail,
                'loginUrl' => config('app.url') . '/client/login',
                'currentYear' => date('Y'),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'client_account_created',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'client_email' => $this->client->email,
            'vendor_name' => $this->vendor->business_name,
            'has_temporary_password' => !empty($this->temporaryPassword),
        ];
    }
}
