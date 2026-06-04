<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRegistrationWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries;
    public $timeout;

    protected $user;
    protected $vendor;

    public function __construct(User $user, Vendor $vendor)
    {
        $this->user = $user;
        $this->vendor = $vendor;

        $this->tries = config('notifications.tries', 3);
        $this->timeout = config('notifications.timeout', 30);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function viaQueues(): array
    {
        return [
            'mail' => config('notifications.queue.emails', 'emails'),
        ];
    }

    public function backoff(): array
    {
        return config('notifications.backoff', [10, 30, 60]);
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'Our Platform');
        $supportEmail = config('mail.support_email', 'support@example.com');
        $dashboardUrl = config('app.url') . '/vendor/dashboard';

        return (new MailMessage)
            ->subject('Welcome to ' . $appName . ' - Your Vendor Account is Ready!')
            ->view('emails.vendor.welcome', [
                'user' => $this->user,
                'vendor' => $this->vendor,
                'appName' => $appName,
                'supportEmail' => $supportEmail,
                'dashboardUrl' => $dashboardUrl,
                'registrationDate' => $this->vendor->created_at?->format('F j, Y') ?? now()->format('F j, Y'),
                'currentYear' => date('Y'),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'vendor_registration_welcome',
            'user_id' => $this->user->id,
            'vendor_id' => $this->vendor->id,
            'vendor_name' => $this->vendor->business_name,
            'user_email' => $this->user->email,
        ];
    }
}
