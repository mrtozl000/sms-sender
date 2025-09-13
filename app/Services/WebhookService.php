<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for sending messages via external webhook.
 *
 * Wraps HTTP requests with proper headers and error handling.
 *
 */
class WebhookService
{
    /**
     * Webhook endpoint URL.
     *
     * @var string
     */
    protected string $webhookUrl;

    /**
     * Authentication key for the webhook.
     *
     * @var string
     */
    protected string $authKey;

    /**
     * WebhookService constructor.
     *
     * Reads configuration values from config/sms.php
     */
    public function __construct()
    {
        $this->webhookUrl = config('sms.webhook_url');
        $this->authKey = config('sms.auth_key');
    }

    /**
     * Send a message payload to the webhook service.
     *
     * @param string $phoneNumber Recipient phone number.
     * @param string $content     Message text content.
     * @param array  $options     Optional settings, e.g. ['idempotency_key' => '...']
     *
     * @return array{
     *     success: bool,
     *     message_id?: string|null,
     *     response?: array|null,
     *     error?: string
     * }
     */
    public function sendMessage(string $phoneNumber, string $content, array $options = []): array
    {
        try {

            $headers = [
                'Content-Type' => 'application/json',
                'x-ins-auth-key' => $this->authKey,
            ];
            if (!empty($options['idempotency_key'])) {
                $headers['Idempotency-Key'] = $options['idempotency_key'];
            }
            $response = Http::withHeaders($headers)->post($this->webhookUrl, [
                'to' => $phoneNumber,
                'content' => $content,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['messageId'] ?? null,
                    'response' => $data,
                ];
            }

            Log::error('Webhook request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Request failed with status: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Webhook request exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
