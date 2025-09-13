<?php

namespace Tests\Feature\Api;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_sent_messages_endpoint()
    {
        // Create sent messages
        Message::factory()->count(5)->create([
            'is_sent' => true,
            'message_id' => 'test-id',
            'sent_at' => now(),
        ]);

        $response = $this->getJson('/api/messages/sent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'phone_number',
                        'content',
                        'message_id',
                        'sent_at',
                        'cached_data',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page',
                    'last_page',
                ],
            ]);
    }

    public function test_get_sent_messages_with_filters()
    {
        Message::factory()->create([
            'is_sent' => true,
            'phone_number' => '+905551234567',
            'sent_at' => now()->subDays(2),
        ]);

        Message::factory()->create([
            'is_sent' => true,
            'phone_number' => '+905559876543',
            'sent_at' => now(),
        ]);

        $response = $this->getJson('/api/messages/sent?phone_number=1234');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('1234', $data[0]['phone_number']);
    }

    public function test_create_message_endpoint()
    {
        $data = [
            'phone_number' => '+905551234567',
            'content' => 'Test message content',
        ];

        $response = $this->postJson('/api/messages', $data);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Message created successfully',
            ]);

        $this->assertDatabaseHas('messages', [
            'phone_number' => '+905551234567',
            'content' => 'Test message content',
            'is_sent' => false,
        ]);
    }

    public function test_create_message_validation_error()
    {
        $data = [
            'phone_number' => '+905551234567',
            // content is missing
        ];

        $response = $this->postJson('/api/messages', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }
}
