<?php

namespace App\Services\Notification;

use App\Models\Vendor;
use App\Models\User;
use App\Notifications\VendorRegistrationAdminNotification;
use App\Notifications\VendorRegistrationWelcomeNotification;
use App\Notifications\NewEmployeeAccountNotification;
use App\Notifications\NewClientAccountNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function sendVendorRegistrationNotifications(User $user, Vendor $vendor): void
    {
        try {
            if (!$this->isEmailNotificationsEnabled()) {
                Log::info('Email notifications are disabled, skipping vendor registration notifications');
                return;
            }

            $this->sendVendorWelcomeEmail($user, $vendor);
            $this->sendVendorRegistrationAdminAlert($user, $vendor);

            Log::info('✅ Vendor registration notifications queued successfully', [
                'vendor_id' => $vendor->id,
                'business_name' => $vendor->business_name
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue vendor registration notifications', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendVendorWelcomeEmail(User $user, Vendor $vendor): void
    {
        if (!$this->isEmailNotificationsEnabled()) {
            Log::info('Email notifications are disabled, skipping vendor welcome email');
            return;
        }

        if (!Config::get('notifications.registration.send_vendor_welcome_email', true)) {
            Log::info('Vendor welcome email disabled by configuration');
            return;
        }

        try {
            $welcomeDelay = Config::get('notifications.registration.vendor_welcome_email_delay', 0);
            $notification = new VendorRegistrationWelcomeNotification($user, $vendor);

            if ($welcomeDelay > 0) {
                $notification->delay(now()->addSeconds($welcomeDelay));
            }

            $user->notify($notification);

            Log::info('Vendor welcome email queued', [
                'vendor_id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'email' => $user->email,
                'delay_seconds' => $welcomeDelay
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send vendor welcome email', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendVendorRegistrationAdminAlert(User $user, Vendor $vendor): void
    {
        if (!$this->isEmailNotificationsEnabled()) {
            Log::info('Email notifications are disabled, skipping vendor admin alert');
            return;
        }

        if (!Config::get('notifications.registration.send_admin_notification', true)) {
            Log::info('Vendor registration admin notification disabled by configuration');
            return;
        }

        try {
            $delay = Config::get('notifications.registration.admin_notification_delay', 0);
            $notification = new VendorRegistrationAdminNotification($user, $vendor);

            if ($delay > 0) {
                $notification->delay(now()->addSeconds($delay));
            }

            $adminUsers = $this->getAdminUsers();

            if ($adminUsers->isEmpty()) {
                $this->sendToFallbackEmail($user, $vendor, $delay);
            } else {
                Notification::send($adminUsers, $notification);
                Log::info('Vendor registration admin alert queued', [
                    'vendor_id' => $vendor->id,
                    'business_name' => $vendor->business_name,
                    'admin_count' => $adminUsers->count(),
                    'delay_seconds' => $delay
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vendor registration admin alert', [
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function sendNewEmployeeNotification(User $employee, Vendor $vendor, User $createdBy, ?string $temporaryPassword = null): void
    {
        if (!$this->isEmailNotificationsEnabled()) {
            Log::info('Email notifications are disabled, skipping new employee notification');
            return;
        }

        try {
            // Notify the new employee
            if ($employee->email) {
                $employee->notify(new NewEmployeeAccountNotification($employee, $vendor, $temporaryPassword));
                Log::info('New employee account notification sent', [
                    'employee_id' => $employee->id,
                    'vendor_id' => $vendor->id,
                    'has_temp_password' => !empty($temporaryPassword)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send new employee notification', [
                'employee_id' => $employee->id,
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendNewClientNotification(User $client, Vendor $vendor, User $createdBy, ?string $temporaryPassword = null): void
    {
        if (!$this->isEmailNotificationsEnabled()) {
            Log::info('Email notifications are disabled, skipping new client notification');
            return;
        }

        try {
            // Notify the new client
            if ($client->email) {
                $client->notify(new NewClientAccountNotification($client, $vendor, $temporaryPassword));
                Log::info('New client account notification sent', [
                    'client_id' => $client->id,
                    'vendor_id' => $vendor->id,
                    'has_temp_password' => !empty($temporaryPassword)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send new client notification', [
                'client_id' => $client->id,
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function isEmailNotificationsEnabled(): bool
    {
        return Config::get('notifications.email.enabled', true);
    }
    private function getAdminUsers(): \Illuminate\Support\Collection
    {
        // Get platform admins (not vendor owners)
        return User::whereHas('roles', function ($query) {
            $query->where('slug', 'platform_admin');
        })
            ->where('is_active', true)
            ->whereNull('vendor_id') // Platform admins should not have vendor association
            ->get();
    }

    private function sendToFallbackEmail(User $user, Vendor $vendor, int $delay): void
    {
        $adminEmail = $this->getAdminEmail();

        if ($adminEmail) {
            try {
                $notification = new NewVendorRegistrationNotification($user, $vendor);

                if ($delay > 0) {
                    $notification->delay(now()->addSeconds($delay));
                }

                Notification::route('mail', $adminEmail)->notify($notification);

                Log::warning('Admin notification sent to fallback email', [
                    'email' => $adminEmail,
                    'vendor_id' => $vendor->id,
                    'reason' => 'No admin users found'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send to fallback email', [
                    'admin_email' => $adminEmail,
                    'vendor_id' => $vendor->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function getAdminEmail(): ?string
    {
        $sources = [
            'notification.admin.email',
            'mail.admin_email',
            'notification.registration.admin_email_fallback',
            'mail.from.address',
            'MAIL_ADMIN_EMAIL'
        ];

        foreach ($sources as $source) {
            $email = Config::get($source, env($source));
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== 'hello@example.com') {
                return $email;
            }
        }

        return null;
    }
}
