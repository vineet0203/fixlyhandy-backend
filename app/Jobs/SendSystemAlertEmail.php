<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSystemAlertEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $message,
        private string $level,
        private array $context,
        private string $category,
        private string $adminEmail
    ) {}

    public function handle(): void
    {
        $subject = "[{$this->level}] {$this->category}: " . substr($this->message, 0, 50) . (strlen($this->message) > 50 ? '...' : '');

        Mail::raw($this->formatContent(), function ($mail) use ($subject) {
            $mail->to($this->adminEmail)
                ->subject($subject);
        });
    }

    private function formatContent(): string
    {
        $appName = config('app.name');
        $env = config('app.env');
        $url = config('app.url');

        $content = "{$appName} - System Alert\n";
        $content .= "Environment: {$env}\n";
        $content .= "URL: {$url}\n";
        $content .= "Time: " . now()->toDateTimeString() . "\n";
        $content .= "Level: {$this->level}\n";
        $content .= "Category: {$this->category}\n";
        $content .= "Message: {$this->message}\n\n";

        if (config('logging.system_logger.email.include_trace', false) && isset($this->context['trace'])) {
            $content .= "Trace:\n{$this->context['trace']}\n\n";
        }

        $content .= "Context:\n" . json_encode($this->context, JSON_PRETTY_PRINT) . "\n\n";
        $content .= "---\n";
        $content .= "This is an automated message from {$appName} system monitoring.\n";

        return $content;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send system alert email', [
            'message' => $this->message,
            'error' => $exception->getMessage(),
            'admin_email' => $this->adminEmail,
        ]);
    }
}
