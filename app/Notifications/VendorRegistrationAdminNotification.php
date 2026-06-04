<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRegistrationAdminNotification extends Notification implements ShouldQueue
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
        $adminDashboardUrl = config('app.url') . '/admin/vendors';
        $vendorUrl = $adminDashboardUrl . '/' . $this->vendor->id;

        return (new MailMessage)
            ->subject('New Vendor Registration - ' . $this->vendor->business_name)
            ->view('emails.admin.vendor-registration', [
                'admin' => $notifiable,
                'user' => $this->user,
                'vendor' => $this->vendor,
                'appName' => $appName,
                'adminDashboardUrl' => $adminDashboardUrl,
                'vendorUrl' => $vendorUrl,
                'registrationDate' => $this->vendor->created_at?->format('F j, Y H:i') ?? now()->format('F j, Y H:i'),
                'currentYear' => date('Y'),
                'supportEmail' => config('mail.support_email', 'support@example.com'),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'vendor_registration_admin',
            'vendor_id' => $this->vendor->id,
            'vendor_name' => $this->vendor->business_name,
            'user_email' => $this->user->email,
            'registration_date' => $this->vendor->created_at,
        ];
    }
}
