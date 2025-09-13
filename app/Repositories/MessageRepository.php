<?php
/**
 * Repository implementation for managing Message entities.
 *
 * Provides methods to find, create, update, and query messages.
 *
 * @package    App\Repositories
 * @subpackage Message
 */

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class MessageRepository
 *
 * Handles database operations related to messages.
 */
class MessageRepository implements MessageRepositoryInterface
{
    /**
     * The Message model instance.
     *
     * @var Message
     */
    protected Message $model;

    /**
     * MessageRepository constructor.
     *
     * @param Message $model The Message model instance.
     */
    public function __construct(Message $model)
    {
        $this->model = $model;
    }

    /**
     * Find a message by its ID.
     *
     * @param int $id Message ID.
     * @return Message|null
     */
    public function findById(int $id): ?Message
    {
        return $this->model->find($id);
    }

    /**
     * Get unsent messages with attempt count less than 3.
     *
     * @param int $limit Number of messages to fetch.
     * @return Collection<Message>
     */
    public function getUnsentMessages(int $limit): Collection
    {
        return $this->model
            ->unsent()
            ->where('attempts', '<', 3)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sent messages with optional filters.
     *
     * @param array $filters Filters: from_date, to_date, phone_number, per_page.
     * @return LengthAwarePaginator
     */
    public function getSentMessages(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->sent();

        if (isset($filters['from_date'])) {
            $query->whereDate('sent_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('sent_at', '<=', $filters['to_date']);
        }

        if (isset($filters['phone_number'])) {
            $query->where('phone_number', 'like', '%' . $filters['phone_number'] . '%');
        }

        return $query
            ->orderBy('sent_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Mark a message as sent.
     *
     * @param Message $message   The message instance.
     * @param string  $messageId The external message ID.
     * @return void
     */
    public function markAsSent(Message $message, string $messageId): void
    {
        $message->markAsSent($messageId);
    }

    /**
     * Increment the attempts counter for a message.
     *
     * @param Message     $message The message instance.
     * @param string|null $error   Optional error message.
     * @return void
     */
    public function incrementAttempts(Message $message, ?string $error = null): void
    {
        $message->incrementAttempts($error);
    }

    /**
     * Create a new message.
     *
     * @param array $data Message data.
     * @return Message
     */
    public function create(array $data): Message
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing message.
     *
     * @param Message $message The message instance.
     * @param array   $data    Data to update.
     * @return bool
     */
    public function update(Message $message, array $data): bool
    {
        return $message->update($data);
    }
}
