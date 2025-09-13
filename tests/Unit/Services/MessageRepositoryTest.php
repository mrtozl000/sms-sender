<?php

namespace Tests\Unit\Repositories;

use App\Models\Message;
use App\Repositories\MessageRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected MessageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MessageRepository(new Message());
    }

    public function test_get_unsent_messages()
    {
        // Create sent messages
        Message::factory()->count(3)->create(['is_sent' => true]);

        // Create unsent messages
        $unsentMessages = Message::factory()->count(5)->create(['is_sent' => false]);

        $result = $this->repository->getUnsentMessages(3);

        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn($m) => !$m->is_sent));
    }

    public function test_get_sent_messages_with_filters()
    {
        // Create messages with different dates
        Message::factory()->create([
            'is_sent' => true,
            'sent_at' => now()->subDays(5),
            'phone_number' => '+905551111111',
        ]);

        Message::factory()->create([
            'is_sent' => true,
            'sent_at' => now()->subDays(2),
            'phone_number' => '+905552222222',
        ]);

        Message::factory()->create([
            'is_sent' => true,
            'sent_at' => now(),
            'phone_number' => '+905553333333',
        ]);

        $filters = [
            'from_date' => now()->subDays(3)->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ];

        $result = $this->repository->getSentMessages($filters);

        $this->assertEquals(2, $result->total());
    }

    public function test_mark_as_sent()
    {
        $message = Message::factory()->create(['is_sent' => false]);

        $this->repository->markAsSent($message, 'external-123');

        $message->refresh();

        $this->assertTrue($message->is_sent);
        $this->assertEquals('external-123', $message->message_id);
        $this->assertNotNull($message->sent_at);
    }

    public function test_increment_attempts()
    {
        $message = Message::factory()->create(['attempts' => 0]);

        $this->repository->incrementAttempts($message, 'Test error');

        $message->refresh();

        $this->assertEquals(1, $message->attempts);
        $this->assertEquals('Test error', $message->last_error);
    }
}
