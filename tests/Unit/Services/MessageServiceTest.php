<?php

namespace Tests\Unit\Services;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Services\MessageService;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    protected MessageService $service;
    protected $mockRepository;
    protected $mockWebhookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(MessageRepositoryInterface::class);
        $this->mockWebhookService = Mockery::mock(WebhookService::class);

        $this->service = new MessageService(
            $this->mockRepository,
            $this->mockWebhookService
        );
    }

    public function test_send_message_success()
    {
        $message = new Message([
            'id' => 1,
            'phone_number' => '+905551234567',
            'content' => 'Test message',
        ]);

        $this->mockWebhookService
            ->shouldReceive('sendMessage')
            ->once()
            ->with($message->phone_number, $message->content)
            ->andReturn([
                'success' => true,
                'message_id' => 'external-123',
            ]);

        $this->mockRepository
            ->shouldReceive('markAsSent')
            ->once()
            ->with($message, 'external-123');

        Cache::shouldReceive('put')->once();

        $result = $this->service->sendMessage($message);

        $this->assertTrue($result['success']);
        $this->assertEquals('external-123', $result['message_id']);
    }

    public function test_send_message_failure()
    {
        $message = new Message([
            'id' => 1,
            'phone_number' => '+905551234567',
            'content' => 'Test message',
        ]);

        $this->mockWebhookService
            ->shouldReceive('sendMessage')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Connection failed',
            ]);

        $this->mockRepository
            ->shouldReceive('incrementAttempts')
            ->once()
            ->with($message, 'Connection failed');

        $result = $this->service->sendMessage($message);

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection failed', $result['error']);
    }

    public function test_message_content_length_validation()
    {
        $longContent = str_repeat('a', 200);
        $message = new Message([
            'id' => 1,
            'phone_number' => '+905551234567',
            'content' => $longContent,
        ]);

        $this->mockWebhookService
            ->shouldReceive('sendMessage')
            ->once()
            ->with($message->phone_number, substr($longContent, 0, 160))
            ->andReturn([
                'success' => true,
                'message_id' => 'external-123',
            ]);

        $this->mockRepository
            ->shouldReceive('markAsSent')
            ->once();

        Cache::shouldReceive('put')->once();

        $result = $this->service->sendMessage($message);

        $this->assertTrue($result['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
