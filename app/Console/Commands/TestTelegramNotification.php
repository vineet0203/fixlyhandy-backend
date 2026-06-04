<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestTelegramNotification extends Command
{
    protected $signature = 'telegram:test';
    protected $description = 'Test Telegram deployment notifications';

    public function handle()
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        
        if (empty($botToken) || empty($chatId)) {
            $this->error('Telegram is not configured in .env file!');
            $this->line('Please add to your .env file:');
            $this->line('TELEGRAM_BOT_TOKEN=your_bot_token_here');
            $this->line('TELEGRAM_CHAT_ID=your_chat_id_here');
            $this->line('');
            $this->line('Current values:');
            $this->line('TELEGRAM_BOT_TOKEN=' . env('TELEGRAM_BOT_TOKEN', 'NOT SET'));
            $this->line('TELEGRAM_CHAT_ID=' . env('TELEGRAM_CHAT_ID', 'NOT SET'));
            return 1;
        }

        $this->info('Testing Telegram bot token...');
        $this->line('Bot Token: ' . substr($botToken, 0, 10) . '...');
        $this->line('Chat ID: ' . $chatId);
        
        // Disable SSL verification for local testing
        $httpClient = Http::withOptions([
            'verify' => app()->environment('production') ? true : false,
        ]);

        // First test if bot token is valid
        try {
            $response = $httpClient->get("https://api.telegram.org/bot{$botToken}/getMe");
            
            if (!$response->successful()) {
                $this->error('❌ Invalid bot token or bot not found');
                $this->line('Response: ' . $response->body());
                
                if (strpos($response->body(), '404') !== false) {
                    $this->line("\n⚠️  Bot token appears to be invalid.");
                    $this->line('Get a new token from @BotFather on Telegram');
                }
                
                return 1;
            }
            
            $botInfo = $response->json();
            $this->info('✅ Bot found: ' . $botInfo['result']['first_name']);
            $this->line('Username: @' . $botInfo['result']['username']);
            
        } catch (\Exception $e) {
            $this->error('❌ Error connecting to Telegram: ' . $e->getMessage());
            
            // Try alternative method without SSL verification
            $this->info("\nTrying alternative method...");
            return $this->testWithCurl($botToken, $chatId);
        }

        $this->info("\nSending test message...");
        
        // Send test message
        try {
            $response = $httpClient->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "🔔 *Test Notification*\n\nYour deployment notification system is working!\nEnvironment: " . app()->environment() . "\nTime: " . date('Y-m-d H:i:s'),
                'parse_mode' => 'Markdown',
            ]);
            
            if ($response->successful()) {
                $this->info('✅ Test notification sent successfully!');
                $this->line('Check your Telegram group/channel.');
                
                $responseData = $response->json();
                if (isset($responseData['result']['message_id'])) {
                    $this->line('Message ID: ' . $responseData['result']['message_id']);
                }
                
            } else {
                $this->error('❌ Failed to send test notification');
                $this->line('Error: ' . $response->body());
                
                $body = $response->body();
                if (strpos($body, 'chat not found') !== false) {
                    $this->line("\n⚠️  Chat not found. Make sure:");
                    $this->line('1. Your bot is added to the group/channel');
                    $this->line('2. The chat ID is correct');
                    $this->line('3. For channels, chat ID should start with -100');
                } elseif (strpos($body, 'Forbidden') !== false) {
                    $this->line("\n⚠️  Bot is forbidden from sending to this chat.");
                    $this->line('Make sure the bot is added as an administrator in channels.');
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            return $this->testWithCurl($botToken, $chatId);
        }
        
        return 0;
    }
    
    private function testWithCurl($botToken, $chatId)
    {
        $this->info('Testing with direct cURL...');
        
        // Test bot token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getMe");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL for local
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $this->error('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return 1;
        }
        
        curl_close($ch);
        
        $data = json_decode($result, true);
        if (!$data || !isset($data['ok']) || !$data['ok']) {
            $this->error('Invalid bot token response');
            $this->line('Response: ' . $result);
            return 1;
        }
        
        $this->info('✅ Bot found via cURL: ' . $data['result']['first_name']);
        
        // Send test message
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'chat_id' => $chatId,
            'text' => 'Test from Laravel via cURL',
            'parse_mode' => 'HTML',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $this->error('cURL send error: ' . curl_error($ch));
        } else {
            $response = json_decode($result, true);
            if ($response && isset($response['ok']) && $response['ok']) {
                $this->info('✅ Test message sent via cURL!');
            } else {
                $this->error('Failed to send: ' . $result);
            }
        }
        
        curl_close($ch);
        return 0;
    }
}