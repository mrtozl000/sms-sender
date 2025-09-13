<?php
namespace App\Console\Commands;

use App\Jobs\SendMessageJob;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessMessagesCommand
 *
 * Console command to process or queue unsent messages.
 */
class ProcessMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:process
                            {--limit=2 : Number of messages}
                            {--use-queue : Dispatch to queue }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process unsent messages and send them via webhook';

    /**
     * Message repository dependency.
     */
    protected MessageRepositoryInterface $messageRepository;

    /**
     * Constructor.
     *
     * @param MessageRepositoryInterface $messageRepository Repository for retrieving unsent messages.
     */
    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        parent::__construct();
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int Command exit code.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $useQueue = $this->option('use-queue');

        $this->info("Processing up to {$limit} unsent messages...");

        $messages = $this->messageRepository->getUnsentMessages($limit);

        if ($messages->isEmpty()) {
            $this->info('No unsent messages found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$messages->count()} messages to process.");

        $bar = $this->output->createProgressBar($messages->count());
        $bar->start();

        foreach ($messages as $message) {
            if ($useQueue) {
                // Dispatch to queue
                SendMessageJob::dispatch($message);
                $this->line("\nMessage {$message->id} dispatched to queue.");
            } else {
                // Process immediately (for testing/debugging)
                SendMessageJob::dispatchSync($message);
                $this->line("\nMessage {$message->id} processed directly.");
            }

            $bar->advance();

            // Sleep for interval between messages
            if (!$messages->last()->is($message)) {
                sleep(config('sms.interval_seconds', 5));
            }
        }

        $bar->finish();
        $this->newLine();

        if ($useQueue) {
            $this->info("All messages dispatched to queue. Run 'php artisan queue:work' to process them.");
        } else {
            $this->info('All messages processed.');
        }

        return Command::SUCCESS;
    }
}
