<?php
namespace App\Services;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service layer for creating, sending, listing and caching messages.
 *
 * Coordinates repository I/O with external webhook delivery and simple caching.
 *
 */
class MessageService
{
    /**
     * @var MessageRepositoryInterface
     */
    protected MessageRepositoryInterface $messageRepository;

    /**
     * @var WebhookService
     */
    protected WebhookService $webhookService;

    /**
     * Maximum allowed message content length (from config: sms.max_length).
     *
     * @var int
     */
    protected int $maxLength;

    /**
     * MessageService constructor.
     *
     * @param MessageRepositoryInterface $messageRepository Repository for message persistence.
     * @param WebhookService $webhookService Transport for outbound webhook sends.
     */
    public function __construct(
        MessageRepositoryInterface $messageRepository,
        WebhookService             $webhookService
    )
    {
        $this->messageRepository = $messageRepository;
        $this->webhookService = $webhookService;
        $this->maxLength = config('sms.max_length', 160);
    }

    /**
     * Process up to the given limit of unsent messages and attempt delivery.
     *
     * @param int $limit Maximum number of unsent messages to process.
     * @return array{
     *     processed:int,
     *     successful:int,
     *     failed:int,
     *     details:array<int, array{
     *         message_id:int,
     *         success:bool,
     *         external_id:?string,
     *         error:?string
     *     }>
     * }
     */
    public function processUnsentMessages(int $limit): array
    {
        $messages = $this->messageRepository->getUnsentMessages($limit);
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($messages as $message) {
            $result = $this->sendMessage($message);
            $results['processed']++;

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'message_id' => $message->id,
                'success' => $result['success'],
                'external_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Send a single message via the configured webhook and update persistence/cache.
     *
     * Truncates content to configured max length. On success, marks message as sent and
     * caches the external ID. On failure, increments attempt counters.
     *
     * @param Message $message The message model to send.
     * @return array{success:bool, message_id?:string, error?:string}
     */
    public function sendMessage(Message $message): array
    {
        try {

            if (strlen($message->content) > $this->maxLength) {
                Log::warning("Message content exceeds maximum length", [
                    'message_id' => $message->id,
                    'length' => strlen($message->content),
                    'max_length' => $this->maxLength,
                ]);

                $content = substr($message->content, 0, $this->maxLength);
            } else {
                $content = $message->content;
            }


            // Build a deterministic idempotency key to prevent duplicate sends on retries
            $normalizedPhone = preg_replace('/\D+/', '', (string)$message->phone_number);
            $shortHash = substr(
                hash_hmac('sha256', $message->id . '|' . $normalizedPhone . '|' . $content, (string)config('app.key')),
                0,
                16
            );
            $idempotencyKey = "msg:{$message->id}:{$shortHash}";
            Log::debug('Prepared idempotency key for webhook send', [
                'message_id' => $message->id,
                'idempotency_key' => $idempotencyKey,
            ]);
            // Send via webhook
            $response = $this->webhookService->sendMessage(
                $message->phone_number,
                $content,
                [
                    'idempotency_key' => $idempotencyKey,
                ]
            );

            if ($response['success']) {
                $this->messageRepository->markAsSent($message, $response['message_id']);

                $this->cacheMessageData($message->id, $response['message_id']);

                Log::info("Message sent successfully", [
                    'message_id' => $message->id,
                    'external_id' => $response['message_id'],
                ]);

                return [
                    'success' => true,
                    'message_id' => $response['message_id'],
                ];
            } else {
                $this->messageRepository->incrementAttempts($message, $response['error']);

                Log::error("Failed to send message", [
                    'message_id' => $message->id,
                    'error' => $response['error'],
                ]);

                return [
                    'success' => false,
                    'error' => $response['error'],
                ];
            }
        } catch (\Exception $e) {
            $this->messageRepository->incrementAttempts($message, $e->getMessage());

            Log::error("Exception while sending message", [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Paginated list of sent messages using optional filters.
     *
     * @param array<string,mixed> $filters Filter array (e.g., date ranges, phone number).
     * @return LengthAwarePaginator
     */
    public function getSentMessages(array $filters = []): LengthAwarePaginator
    {
        return $this->messageRepository->getSentMessages($filters);
    }

    /**
     * Create a new message after validating content length.
     *
     * @param array<string,mixed> $data Message payload (expects at least 'phone_number' and 'content').
     * @return Message
     * @throws \InvalidArgumentException If content exceeds configured max length.
     */
    public function createMessage(array $data): Message
    {
        // Validate content length before creating
        if (strlen($data['content']) > $this->maxLength) {
            throw new \InvalidArgumentException(
                "Message content exceeds maximum length of {$this->maxLength} characters"
            );
        }

        return $this->messageRepository->create($data);
    }

    /**
     * Cache minimal message delivery metadata for quick lookups.
     *
     * @param int $messageId Local message ID.
     * @param string $externalMessageId External/provider message ID.
     * @return void
     */
    protected function cacheMessageData(int $messageId, string $externalMessageId): void
    {
        $cacheKey = "message:{$messageId}";
        $cacheData = [
            'external_id' => $externalMessageId,
            'sent_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $cacheData, now()->addDays(7));
    }

    /**
     * Retrieve cached delivery metadata for a message if present.
     *
     * @param int $messageId Local message ID.
     * @return array|null Returns ['external_id' => string, 'sent_at' => string] or null if not cached.
     */
    public function getCachedMessageData(int $messageId): ?array
    {
        $cacheKey = "message:{$messageId}";
        return Cache::get($cacheKey);
    }
}
