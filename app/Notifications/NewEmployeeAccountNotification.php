<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEmployeeAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $employee;
    protected $vendor;
    protected $temporaryPassword;
    public $tries;
    public $timeout;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $employee, Vendor $vendor, $temporaryPassword = null)
    {
        $this->employee = $employee;
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
            ->subject('Welcome to ' . $this->vendor->business_name . ' - Employee Account Created')
            ->markdown('emails.employee-welcome', [
                'employee' => $this->employee,
                'vendor' => $this->vendor,
                'temporaryPassword' => $this->temporaryPassword,
                'appName' => $appName,
                'supportEmail' => $supportEmail,
                'loginUrl' => config('app.url') . '/employee/login',
                'dashboardUrl' => config('app.url') . '/employee/dashboard',
                'currentYear' => date('Y'),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'employee_account_created',
            'employee_id' => $this->employee->id,
            'vendor_id' => $this->vendor->id,
            'employee_email' => $this->employee->email,
            'vendor_name' => $this->vendor->business_name,
            'has_temporary_password' => !empty($this->temporaryPassword),
        ];
    }
}
