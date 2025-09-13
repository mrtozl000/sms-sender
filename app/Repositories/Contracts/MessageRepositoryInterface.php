<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contract for Message repository operations.
 *
 * @package App\Repositories\Contracts
 */
interface MessageRepositoryInterface
{
    /**
     * Find a message by its primary key.
     *
     * @param int $id
     * @return Message|null
     */
    public function findById(int $id): ?Message;

    /**
     * Get unsent messages up to a limit.
     *
     * Returned collection contains Message models ordered by creation time,
     * filtered by retry threshold.
     *
     * @param int $limit
     * @return Collection<int, Message>
     */
    public function getUnsentMessages(int $limit): Collection;

    /**
     * Get a paginated list of sent messages with optional filters.
     *
     * Supported filters:
     * - from_date: string (Y-m-d)
     * - to_date: string (Y-m-d)
     * - phone_number: string (partial match)
     * - per_page: int (default 15)
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function getSentMessages(array $filters = []): LengthAwarePaginator;

    /**
     * Mark the given message as sent with provider message ID.
     *
     * @param Message $message
     * @param string  $messageId
     * @return void
     */
    public function markAsSent(Message $message, string $messageId): void;

    /**
     * Increment attempts and optionally store last error.
     *
     * @param Message     $message
     * @param string|null $error
     * @return void
     */
    public function incrementAttempts(Message $message, ?string $error = null): void;

    /**
     * Create a new message record.
     *
     * @param array<string, mixed> $data
     * @return Message
     */
    public function create(array $data): Message;

    /**
     * Update the given message with provided attributes.
     *
     * @param Message             $message
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(Message $message, array $data): bool;
}
