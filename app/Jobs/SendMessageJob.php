<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\MessageService;
use App\Exceptions\TransientDeliveryException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Message */
    public Message $message;

    public $tries = 4;

    /**
     * Exponential backoff
     * 1. retry: 10sn, 2. retry: 30sn, 3. retry: 90sn
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param MessageService $messageService
     * @return void
     */
    public function handle(MessageService $messageService): void
    {
        try {
            $messageService->sendMessage($this->message);
            Log::info('Message sent from job.', [
                'message_id' => $this->message->id,
                'attempt'    => $this->attempts(),
            ]);
        } catch (TransientDeliveryException $e) {

            Log::warning('Transient error, will retry with backoff.', [
                'message_id' => $this->message->id,
                'attempt'    => $this->attempts(),
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {

            Log::error('Permanent error, failing the job.', [
                'message_id' => $this->message->id,
                'attempt'    => $this->attempts(),
                'error'      => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }

    /**
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Message job failed permanently', [
            'message_id' => $this->message->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
