<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jenssegers\Agent\Agent;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the notification may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 30;

    protected $metadata;

    public function __construct(array $metadata = [])
    {
        $this->metadata = $metadata;

        // Get values from config
        $this->tries = config('notifications.tries', 3);
        $this->timeout = config('notifications.timeout', 30);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    // Assign specific queue for mail notifications
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
        ];
    }

    // Define backoff intervals for retries
    public function backoff(): array
    {
        return config('notifications.backoff', [10, 30, 60]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'Our Platform');
        $ipAddress = $this->metadata['ip_address'] ?? request()->ip();
        $userAgent = $this->metadata['user_agent'] ?? request()->userAgent();
        $timestamp = $this->metadata['timestamp'] ?? now()->toISOString();
        $location = $this->getLocationFromIp($ipAddress);

        $deviceInfo = $this->parseUserAgent($userAgent);

        return (new MailMessage)
            ->subject('Password Changed Successfully - ' . $appName)
            ->markdown('emails.password-changed', [
                'user' => $notifiable,
                'appName' => $appName,
                'timestamp' => $timestamp,
                'ipAddress' => $ipAddress,
                'location' => $location,
                'deviceInfo' => $deviceInfo,
                'supportEmail' => config('mail.support_email', 'support@example.com'),
                'currentYear' => date('Y'),
            ]);
    }

    /**
     * Get location from IP address (simplified version)
     */
    private function getLocationFromIp(string $ip): string
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }

        $token = config('services.ipinfo.token');
        $url = "https://api.ipinfo.io/lite/{$ip}?token={$token}";

        try {
            $response = file_get_contents($url);
            if (!$response) {
                return 'Unknown Location';
            }

            $data = json_decode($response, true);

            $country = $data['country'] ?? 'Unknown Country';

            return $country; // Lite API only gives country-level data
        } catch (\Exception $e) {
            return 'Unknown Location';
        }
    }


    /**
     * Parse user agent for device/browser info
     */
    private function parseUserAgent(string $userAgent): array
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'browser' => $agent->browser() ?? 'Unknown Browser',
            'os'      => $agent->platform() ?? 'Unknown OS',
            'device'  => $agent->device() ?? ($agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop')),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'password_changed',
            'ip_address' => $this->metadata['ip_address'] ?? null,
            'timestamp' => $this->metadata['timestamp'] ?? null,
        ];
    }
}
